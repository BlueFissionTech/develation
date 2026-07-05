<?php

namespace BlueFission\Tests\HTML;

use BlueFission\HTML\Form;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testSplitDateFormatsDatabaseDateForSelectFields(): void
    {
        $this->assertSame('07', Form::splitDate('2026-07-04', 'month'));
        $this->assertSame('04', Form::splitDate('2026-07-04', 'day'));
        $this->assertSame('2026', Form::splitDate('2026-07-04', 'year'));
        $this->assertSame('07/04/2026', Form::splitDate('2026-07-04'));
    }

    public function testJoinDateBuildsDateFromSubmittedSelectParts(): void
    {
        $date = Form::joinDate('entry', [
            'entry_month' => '7',
            'entry_day' => '4',
            'entry_year' => '2026',
        ]);

        $this->assertSame('7/4/2026', $date);
    }

    public function testJoinDateCanReturnAlternateFormatForValidSubmittedParts(): void
    {
        $date = Form::joinDate('entry', [
            'entry_month' => '7',
            'entry_day' => '4',
            'entry_year' => '2026',
        ], 'Y-m-d');

        $this->assertSame('2026-07-04', $date);
    }

    public function testJoinDateFallsBackToDirectFieldValue(): void
    {
        $this->assertSame('7/4/2026', Form::joinDate('entry', [
            'entry' => '2026-07-04',
        ]));
    }

    public function testJoinDateReadsRequestScopesWithGetTakingPrecedence(): void
    {
        $_POST['publish_month'] = '5';
        $_POST['publish_day'] = '6';
        $_POST['publish_year'] = '2025';
        $_GET['publish_month'] = '7';

        try {
            $this->assertSame('7/6/2025', Form::joinDate('publish'));
        } finally {
            unset(
                $_POST['publish_month'],
                $_POST['publish_day'],
                $_POST['publish_year'],
                $_GET['publish_month']
            );
        }
    }
}
