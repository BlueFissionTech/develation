<?php

namespace BlueFission\Tests\Connections;

use BlueFission\Connections\Curl;
use PHPUnit\Framework\TestCase;

class CurlHandleLifecycleTest extends TestCase
{
    public function testNoNetworkHandleLifecycleHasNoDeprecations(): void
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('The cURL extension is unavailable');
        }

        $connection = new Curl([
            'target' => 'https://example.invalid',
            'validate_host' => false,
        ]);
        $deprecations = [];
        set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool {
            if (($severity & (E_DEPRECATED | E_USER_DEPRECATED)) !== 0) {
                $deprecations[] = $message;
                return true;
            }

            return false;
        });

        try {
            // open() initializes a handle but does not make a network request.
            $this->assertSame($connection, $connection->open());
            $this->assertInstanceOf(\CurlHandle::class, $connection->connection());
            $this->assertSame($connection, $connection->close());
            $this->assertNull($connection->connection());
        } finally {
            if ($connection->connection() !== null) {
                $connection->close();
            }
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }
}
