<?php
namespace BlueFission\Tests;

use PHPUnit\Framework\TestCase;
use BlueFission\Utils\DateTime;

class DateTimeTest extends TestCase
{
    public function testFieldMethod()
    {
        $date = new DateTime();
        $this->assertEquals('Y-m-d', $date->field('date_format'));

        $date->field('date_format', 'm/d/Y');
        $this->assertEquals('m/d/Y', $date->field('date_format'));
    }

    public function testTimestampMethod()
    {
        $date = new DateTime();
        $timestamp = $date->timestamp();

        $this->assertIsInt($timestamp);
        $this->assertEquals(time(), $timestamp);

        $date->field('year', 2022);
        $date->field('month', 1);
        $date->field('day', 1);

        $timestamp = $date->timestamp();

        $this->assertIsInt($timestamp);
        $this->assertNotEquals(time(), $timestamp);
    }

    public function testInfoMethod()
    {
        $date = new DateTime();
        $info = $date->info();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('second', $info);
        $this->assertArrayHasKey('minute', $info);
        $this->assertArrayHasKey('hour', $info);
        $this->assertArrayHasKey('day', $info);
        $this->assertArrayHasKey('month', $info);
        $this->assertArrayHasKey('year', $info);
        $this->assertArrayHasKey('timezone', $info);
        $this->assertArrayHasKey('offset', $info);
    }

    /**
     * @test
     */
    public function testIsConfigurable()
    {
        $date = new DateTime();
        $this->assertEquals('Y-m-d', $date->config('date_format'));
        $this->assertEquals('r', $date->config('time_format'));
        $this->assertEquals('America/New_York', $date->config('timezone'));

        $date->config(['date_format' => 'm/d/Y']);
        $this->assertEquals('m/d/Y', $date->config('date_format'));
        $this->assertEquals('r', $date->config('time_format'));
        $this->assertEquals('America/New_York', $date->config('timezone'));
    }

    /**
     * @test
     */
    public function testField()
    {
        $date = new DateTime();
        $this->assertNotNull($date->field('hour'));
        $this->assertNotNull($date->field('minute'));
        $this->assertNotNull($date->field('second'));
        $this->assertNotNull($date->field('day'));
        $this->assertNotNull($date->field('month'));
        $this->assertNotNull($date->field('year'));

        $this->assertNull($date->field('non_existing_field'));
    }

    /**
     * @test
     */
    public function testTimestamp()
    {
        $date = new DateTime();
        $timestamp = $date->timestamp();
        $this->assertNotNull($timestamp);
        $this->assertInternalType('int', $timestamp);

        $timestamp2 = $date->timestamp('01/01/2022');
        $this->assertNotNull($timestamp2);
        $this->assertInternalType('int', $timestamp2);
        $this->assertEquals(1640995200, $timestamp2);
    }

    /**
     * @test
     */
    public function testInfo()
    {
        $date = new DateTime();
        $info = $date->info();
        $this->assertNotNull($info);
        $this->assertInternalType('array', $info);

        $timestamp = $date->timestamp();
        $info = $date->info($timestamp);
        $this->assertNotNull($info);
        $this->assertInternalType('array', $info);
    }

    public function testDifference()
    {
        $date1 = new DateTime('2022-01-01');
        $date2 = new DateTime('2022-12-31');
        $expected = 364;
        
        $difference = DateTime::difference($date1, $date2, 'years');
        $this->assertEquals($expected, $difference);
    }

}
