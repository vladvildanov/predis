<?php

namespace Predis\Connection\Traits;

use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

class RetryTest extends TestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class() {
            use Retry;
        };
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testRetryOnFalse(): void
    {
        $counter = 0;
        $maxRetries = 3;

        $callback = function () use (&$counter, $maxRetries) {
            if ($counter < $maxRetries) {
                $counter++;
                return null;
            }

            return true;
        };

        $this->assertTrue($this->testClass->retryOnFalse($callback, $maxRetries));
        $this->assertEquals(3, $counter);
    }

    /**
     * @group disconnected
     * @return void
     * @throws Exception
     */
    public function testRetryOnError(): void
    {
        $counter = 0;
        $maxRetries = 3;

        $callback = static function () use (&$counter, $maxRetries) {
            if ($counter < $maxRetries) {
                $counter++;
                throw new Exception();
            }

            return true;
        };

        $onCatchCallback = function (Throwable $e) {
            // Nothing to be executed, just to make sure that all code covered.
        };

        $this->assertTrue($this->testClass->retryOnError($callback, $onCatchCallback, $maxRetries));
        $this->assertEquals(3, $counter);
    }
}
