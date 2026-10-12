<?php
namespace BlueFission\Tests\Net;

use PHPUnit\Framework\TestCase;
use BlueFission\Net\HTTP;
use BlueFission\Tests\Support\TestEnvironment;

require_once __DIR__ . '/../Support/TestEnvironment.php';

class HTTPTest extends TestCase {

    public function testPathSegmentEncodesReservedCharacters() {
        $this->assertSame('tenant%2Fwith%3Freserved%23chars', HTTP::pathSegment('tenant/with?reserved#chars'));
    }

    public function testPathSegmentTrimsWhitespaceAndPathSeparators() {
        $this->assertSame('tenant%20id', HTTP::pathSegment(' /tenant id/ '));
        $this->assertSame('', HTTP::pathSegment(' / '));
    }

    public function testUrlPartsReturnsNormalizedParseResult() {
        $parts = HTTP::urlParts('https://example.com:8443/docs?tab=api');

        $this->assertSame('https', $parts['scheme']);
        $this->assertSame('example.com', $parts['host']);
        $this->assertSame(8443, $parts['port']);
        $this->assertSame('/docs', $parts['path']);
        $this->assertNull(HTTP::urlParts('http:///missing-host'));
    }

    public function testUrlComponentHelpersExtractCommonHosts() {
        $this->assertSame('http', HTTP::urlScheme('http://example.com'));
        $this->assertSame('https', HTTP::urlScheme('https://localhost:8443/status'));
        $this->assertSame('localhost', HTTP::urlHost('https://localhost:8443/status'));
        $this->assertSame('127.0.0.1', HTTP::urlHost('http://127.0.0.1:8080/health'));
        $this->assertSame('[::1]', HTTP::urlHost('http://[::1]:8080/health'));
        $this->assertSame('/status', HTTP::urlPath('https://localhost:8443/status?ok=1'));
        $this->assertSame('status', HTTP::urlPath('https://localhost:8443/status?ok=1', true));
        $this->assertSame(8443, HTTP::urlPort('https://localhost:8443/status'));
    }

    public function testUrlComponentHelpersReturnNullForMissingOrInvalidValues() {
        $this->assertNull(HTTP::urlScheme('/relative/path'));
        $this->assertNull(HTTP::urlHost('/relative/path'));
        $this->assertNull(HTTP::urlHost('http:///missing-host'));
        $this->assertNull(HTTP::urlPath('https://example.com'));
        $this->assertNull(HTTP::urlPort('https://example.com'));
        $this->assertNull(HTTP::urlPort('http:///missing-host'));
    }

    public function testJsonDecodeReturnsStructuredValuesAndDefault() {
        $this->assertSame(['ok' => true], HTTP::jsonDecode('{"ok":true}'));
        $this->assertEquals((object)['ok' => true], HTTP::jsonDecode('{"ok":true}', false));
        $this->assertSame(['fallback' => true], HTTP::jsonDecode('{bad json', true, ['fallback' => true]));
        $this->assertNull(HTTP::jsonDecode('null', true, 'fallback'));
    }

    public function testJsonDecodePassesNativeDepthAndOptionsOnlyWhenRequested() {
        $nested = '{"outer":{"inner":true}}';

        $this->assertSame('fallback', HTTP::jsonDecode($nested, true, 'fallback', 1));
        $this->assertSame(
            json_decode($nested, true, 16, JSON_THROW_ON_ERROR),
            HTTP::jsonDecode($nested, true, 'fallback', 16, JSON_THROW_ON_ERROR)
        );
        $this->assertEquals(
            json_decode($nested, false, 16, JSON_THROW_ON_ERROR),
            HTTP::jsonDecode($nested, false, 'fallback', 16, JSON_THROW_ON_ERROR)
        );
        $this->assertSame(123, HTTP::jsonDecode('123', true, 'fallback', 16, JSON_THROW_ON_ERROR));
        $this->assertNull(HTTP::jsonDecode('null', true, 'fallback', 16, JSON_THROW_ON_ERROR));

        // A prior permissive error must not turn a successful throwing decode into a fallback.
        HTTP::jsonDecode('{bad json', true, 'fallback');
        $this->assertSame(['ok' => true], HTTP::jsonDecode('{"ok":true}', true, 'fallback', 16, JSON_THROW_ON_ERROR));
    }

    public function testJsonDecodeThrowFlagNeverReturnsTheFallbackOnInvalidInput() {
        foreach (['{bad json', "\"\xB1\"", '{"outer":{"inner":true}}'] as $index => $json) {
            try {
                HTTP::jsonDecode($json, true, 'fallback', $index === 2 ? 1 : 16, JSON_THROW_ON_ERROR);
                $this->fail('Invalid JSON must raise JsonException.');
            } catch (\JsonException $exception) {
                $this->assertSame(
                    [JSON_ERROR_SYNTAX, JSON_ERROR_UTF8, JSON_ERROR_DEPTH][$index],
                    $exception->getCode()
                );
            }
        }

        $json = "\"\xB1\"";
        $flags = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE;
        $this->assertSame(json_decode($json, true, 16, $flags), HTTP::jsonDecode($json, true, null, 16, $flags));
    }

    public function testJsonDecodeRejectsInvalidDepthLikeNativeJson() {
        $this->expectException(\ValueError::class);
        HTTP::jsonDecode('{}', true, 'fallback', 0, JSON_THROW_ON_ERROR);
    }

    public function testJsonEncodeUsesNativeEncoderWhenAvailable() {
        $payload = ['ok' => true, 'items' => [1, 'two']];

        $this->assertSame(json_encode($payload), HTTP::jsonEncode($payload));
    }

    public function testJsonEncodeAcceptsNativeFlagsWithoutChangingDefault() {
        $payload = ['path' => 'assets/icon.svg'];

        $this->assertSame('{"path":"assets\\/icon.svg"}', HTTP::jsonEncode($payload));
        $this->assertSame(
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            HTTP::jsonEncode($payload, JSON_UNESCAPED_SLASHES)
        );
    }

    public function testJsonEncodePassesNativeDepthAndStrictOptions() {
        $payload = ['path' => 'assets/icon.svg', 'nested' => ['ok' => true]];
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;

        $this->assertSame(json_encode($payload, $flags, 16), HTTP::jsonEncode($payload, $flags, 16));
        $this->assertSame('null', HTTP::jsonEncode(null, JSON_THROW_ON_ERROR, 16));
        $this->assertSame(json_encode(123, JSON_THROW_ON_ERROR, 16), HTTP::jsonEncode(123, JSON_THROW_ON_ERROR, 16));
        $this->assertFalse(HTTP::jsonEncode("\xB1"));
        $this->assertFalse($this->invokeStaticHttpMethod('jsonEncodeFallback', [$payload, $flags, 16]));
        $this->assertFalse($this->invokeStaticHttpMethod('jsonEncodeFallback', [$payload, 0, 16]));
    }

    public function testJsonEncodeThrowFlagRejectsInvalidValuesAndDepth() {
        $resource = fopen('php://memory', 'r+');
        $recursive = [];
        $recursive['self'] = &$recursive;

        try {
            $cases = [
                ["\xB1", 16, JSON_ERROR_UTF8],
                [$resource, 16, JSON_ERROR_UNSUPPORTED_TYPE],
                [$recursive, 16, JSON_ERROR_RECURSION],
                [['nested' => ['ok' => true]], 1, JSON_ERROR_DEPTH],
            ];

            foreach ($cases as [$value, $depth, $code]) {
                try {
                    HTTP::jsonEncode($value, JSON_THROW_ON_ERROR, $depth);
                    $this->fail('Invalid JSON value must raise JsonException.');
                } catch (\JsonException $exception) {
                    $this->assertSame($code, $exception->getCode());
                }
            }
        } finally {
            fclose($resource);
        }

        $flags = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE;
        $this->assertSame(json_encode("\xB1", $flags, 16), HTTP::jsonEncode("\xB1", $flags, 16));
    }

    public function testJsonEncodeRejectsInvalidDepthLikeNativeJson() {
        $this->expectException(\JsonException::class);
        $this->expectExceptionCode(JSON_ERROR_DEPTH);
        HTTP::jsonEncode([], JSON_THROW_ON_ERROR, 0);
    }

    public function testJsonEncodePreservesNativeFailureAndFallbackFlagBoundary() {
        $this->assertFalse(HTTP::jsonEncode("\xB1"));
        $this->assertFalse(
            $this->invokeStaticHttpMethod('jsonEncodeFallback', [
                ['path' => 'assets/icon.svg'],
                JSON_UNESCAPED_SLASHES,
            ])
        );
    }

    public function testJsonEncodeFallbackHandlesListsObjectsAndEscapes() {
        $payload = [
            'title' => "Hello\nWorld",
            'items' => [1, true, null, 'path/file'],
        ];

        $this->assertSame(
            '{"title":"Hello\\nWorld","items":[1,true,null,"path\\/file"]}',
            $this->invokeStaticHttpMethod('jsonEncodeFallback', [$payload])
        );
    }

    public function testHeaderLineNormalizesNameAndValue() {
        $this->assertSame('Content-Type: application/json', HTTP::headerLine(' Content-Type ', ' application/json '));
    }

    public function testStatusHelpersResolveKnownCodes() {
        $this->assertSame('Not Found', HTTP::statusText(404));
        $this->assertSame('HTTP/1.1 404 Not Found', HTTP::statusLine(404));
        $this->assertSame('HTTP/1.0 404 Not Found', HTTP::statusLine(404, 'HTTP/1.0'));
        $this->assertSame('HTTP/2 200 OK', HTTP::statusLine(200, ' HTTP/2 '));
        $this->assertNull(HTTP::statusText(799));
        $this->assertNull(HTTP::statusLine(799));
    }

    public function testQuery() {
        $formdata = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        $numeric_prefix = 'prefix_';
        $key = 'test_key';
        $expected = 'test_key[key1]=value1&test_key[key2]=value2';
        $actual = HTTP::query($formdata, $numeric_prefix, $key);
        $this->assertEquals($expected, $actual);
    }

    public function testQueryPreservesEncodedSlashesInNestedValues() {
        $formdata = [
            'asset' => [
                'path' => 'images/logo.png',
            ],
        ];

        $this->assertSame('data[asset][path]=images/logo.png', HTTP::query($formdata, '', 'data'));
    }

    public function testUrlExists() {
        if (!TestEnvironment::isNetworkEnabled()) {
            $this->assertFalse(HTTP::urlExists('ftp://example.com'));
            return;
        }

        $url = getenv('DEV_ELATION_HTTP_TEST_URL') ?: 'https://www.bluefission.com';
        $this->assertTrue(HTTP::urlExists($url));
        $this->assertFalse(HTTP::urlExists('http://nonexistent.invalid'));
    }

    public function testDomain() {
        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $expected = '.bluefission.com';
        $actual = HTTP::domain();
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $expected = 'www.bluefission.com';
        $actual = HTTP::domain(true);
        $this->assertEquals($expected, $actual);
    }

    public function testUrl() {
        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTPS'] = 'on';
        $expected = 'https://www.bluefission.com/test';
        $actual = HTTP::url();
        $this->assertEquals($expected, $actual);

        $_SERVER['HTTP_HOST'] = 'www.bluefission.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTPS'] = '';
        $expected = 'http://www.bluefission.com/test';
        $actual = HTTP::url();
        $this->assertEquals($expected, $actual);
    }

    private function invokeStaticHttpMethod(string $method, array $arguments = []): mixed {
        $reflection = new \ReflectionClass(HTTP::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $arguments);
    }

}
