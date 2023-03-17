<?php
namespace BlueFission\Tests\Services;

use BlueFission\Services\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    private $response;

    /**
     * Initialize the Response object to be tested.
     */
    protected function setUp(): void
    {
        $this->response = new Response();
    }

    /**
     * Test fill method with an array as input
     */
    public function testFillWithArray()
    {
        $values = array(
            'id' => 1,
            'list' => [1, 2, 3],
            'data' => [
                'name' => 'John Doe',
                'age' => 30
            ],
            'children' => [
                'child1' => [
                    'id' => 2,
                    'list' => [1, 2, 3],
                    'data' => [
                        'name' => 'Jane Doe',
                        'age' => 25
                    ],
                ]
            ],
            'status' => 'Success',
            'info' => 'Additional information',
        );

        $this->response->fill($values);
        $this->assertEquals(1, $this->response->id);
        $this->assertEquals([1, 2, 3], $this->response->list);
        $this->assertEquals([
            'name' => 'John Doe',
            'age' => 30
        ], $this->response->data);
        $this->assertEquals([
            'child1' => [
                'id' => 2,
                'list' => [1, 2, 3],
                'data' => [
                    'name' => 'Jane Doe',
                    'age' => 25
                ],
            ]
        ], $this->response->children);
        $this->assertEquals('Success', $this->response->status);
        $this->assertEquals('Additional information', $this->response->info);
    }

    /**
     * Test fill method with a numeric value as input
     */
    public function testFillWithNumeric()
    {
        $this->response->fill(1);
        $this->assertEquals(1, $this->response->id);
    }

    /**
     * Test fill method with a string value as input
     */
    public function testFillWithString()
    {
        $this->response->fill('Success');
        $this->assertEquals('Success', $this->response->status);
    }

    /**
     * Test fill method with an object as input
     */
    public function testFillWithObject()
    {
        $values = new stdClass();
        $values->name = 'John Doe';
        $values->age = 30;

        $this->response->fill($values);
        $this->assertEquals($values, $this->response->data);
    }

    /**
     * Test message method
     */
    public function testMessage()
    {
        $values = array(
            'id' => 1,
            'list' => [1, 2, 3],
            'data' => [
                'name' => 'John Doe',
                'age' => 30
            ]
        );
        
        $message = new Message($values);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(1, $message->getId());
        $this->assertEquals([1, 2, 3], $message->getList());
        $this->assertEquals('John Doe', $message->getData()['name']);
        $this->assertEquals(30, $message->getData()['age']);
    }
}