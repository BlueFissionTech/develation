<?php

namespace BlueFission\Tests\Data\Storage;

use BlueFission\Data\Storage\Spreadsheet;
use BlueFission\Tests\Support\TestEnvironment;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/TestEnvironment.php';

class SpreadsheetCsvTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = TestEnvironment::tempDir('bf_csv_record');
    }

    protected function tearDown(): void
    {
        TestEnvironment::removeDir($this->directory);
    }

    public function testReadsOneLogicalRecordWithoutReadingLongerLaterRecord(): void
    {
        $first = "alpha,\"two,\r\nlines\",\"q\"\"uote\"\r\n";
        $path = $this->file('records.csv', $first . str_repeat('later', 1000));

        $this->assertSame(['alpha', "two,\r\nlines", 'q"uote'],
            Spreadsheet::firstCsvRecordBounded($path, strlen($first)));
        $this->assertFileExists($path);
        $this->assertTrue(unlink($path));
    }

    public function testDistinguishesEmptyFileFromEmptyAndZeroValuedRecords(): void
    {
        $this->assertNull(Spreadsheet::firstCsvRecordBounded($this->file('empty.csv', ''), 0));
        $this->assertSame([''], Spreadsheet::firstCsvRecordBounded($this->file('blank.csv', "\n"), 1));
        $this->assertSame(['0'], Spreadsheet::firstCsvRecordBounded($this->file('zero.csv', '0'), 1));
        $this->assertSame(['', ''], Spreadsheet::firstCsvRecordBounded($this->file('fields.csv', ','), 1));
        $this->assertSame([''], Spreadsheet::firstCsvRecordBounded($this->file('quoted-empty.csv', "\"\"\r\n"), 4));
    }

    public function testCountsUtf8AndBomBytesAndHonorsBomPolicy(): void
    {
        $path = $this->file('utf8.csv', "\xef\xbb\xbf\xc3\xa9,\"x\ny\"\n");
        $this->assertSame(["\xef\xbb\xbf\xc3\xa9", "x\ny"], Spreadsheet::firstCsvRecordBounded($path, 12));
        $this->assertSame(["\xc3\xa9", "x\ny"], Spreadsheet::firstCsvRecordBounded($path, 12, ',', '"', '', true));
        $this->expectException(\LengthException::class);
        Spreadsheet::firstCsvRecordBounded($path, 11, ',', '"', '', true);
    }

    public function testRejectsCapPlusOneAndMalformedQuoting(): void
    {
        $path = $this->file('cap.csv', "a,b\nlong-later-record");
        try {
            Spreadsheet::firstCsvRecordBounded($path, 3);
            $this->fail('The first record needs four bytes.');
        } catch (\LengthException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
        $this->assertSame(['a', 'b'], Spreadsheet::firstCsvRecordBounded($path, 4));

        foreach (["a\"b,c\n", "\"unclosed", "\"closed\"x\n"] as $contents) {
            $malformed = $this->file('malformed.csv', $contents);
            try {
                Spreadsheet::firstCsvRecordBounded($malformed, strlen($contents));
                $this->fail('Malformed CSV was accepted.');
            } catch (\DomainException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testRejectsMissingNonRegularAndInvalidTargetsWithoutCreatingThem(): void
    {
        $missing = $this->directory . DIRECTORY_SEPARATOR . 'missing.csv';
        try {
            Spreadsheet::firstCsvRecordBounded($missing, 16);
            $this->fail('Missing file was accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertFileDoesNotExist($missing);
        }

        foreach ([$this->directory, 'php://memory'] as $target) {
            try {
                Spreadsheet::firstCsvRecordBounded($target, 16);
                $this->fail('Nonregular target was accepted.');
            } catch (\InvalidArgumentException|\UnexpectedValueException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testSupportsExplicitDialectAndRejectsInvalidDialect(): void
    {
        $path = $this->file('custom.csv', "a;'b\\'c';d\n");
        $this->assertSame(['a', "b'c", 'd'],
            Spreadsheet::firstCsvRecordBounded($path, 64, ';', "'", '\\'));

        foreach ([['||', '"', ''], [',', ',', ''], [',', '"', ',']] as [$delimiter, $enclosure, $escape]) {
            try {
                Spreadsheet::firstCsvRecordBounded($path, 64, $delimiter, $enclosure, $escape);
                $this->fail('Invalid CSV dialect was accepted.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }

        $malformed = $this->file('invalid-escape.csv', "'bad\\x'\n");
        $this->expectException(\DomainException::class);
        Spreadsheet::firstCsvRecordBounded($malformed, 64, ';', "'", '\\');
    }

    public function testClosesOwnedHandleAfterMalformedAndOversizeRecords(): void
    {
        foreach ([["\"unfinished", 11, \DomainException::class], ['123456', 3, \LengthException::class]] as [$contents, $limit, $exceptionType]) {
            $path = $this->file('cleanup.csv', $contents);
            try {
                Spreadsheet::firstCsvRecordBounded($path, $limit);
                $this->fail('Expected an error.');
            } catch (\DomainException|\LengthException $exception) {
                $this->assertInstanceOf($exceptionType, $exception);
            }
            $this->assertTrue(unlink($path));
        }
    }

    private function file(string $name, string $contents): string
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $contents);
        return $path;
    }
}
