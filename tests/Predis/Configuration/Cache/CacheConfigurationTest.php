<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Cache;

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use PredisTestCase;
use UnexpectedValueException;

class CacheConfigurationTest extends PredisTestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testGetTTl(): void
    {
        $configuration = new CacheConfiguration(['cache_ttl' => 100]);

        $this->assertSame(100, $configuration->getTTl());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testIsWhitelistedCommand(): void
    {
        $configuration = new CacheConfiguration();
        $readCommand = new RawCommand('GET', ['key'], CommandInterface::READ_MODE);
        $writeCommand = new RawCommand('SET', ['key', 'value']);
        $excludedCommand = new RawCommand('TDIGEST.MAX', ['key', 'value'], CommandInterface::READ_MODE);

        $this->assertTrue($configuration->isWhitelistedCommand($readCommand));
        $this->assertFalse($configuration->isWhitelistedCommand($writeCommand));
        $this->assertFalse($configuration->isWhitelistedCommand($excludedCommand));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testIsExceedsMaxSize(): void
    {
        $configuration = new CacheConfiguration(['cache_max_size' => 2]);

        $this->assertFalse($configuration->isExceedsMaxSize(2));
        $this->assertTrue($configuration->isExceedsMaxSize(3));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testReturnsDefaultConfigurationOnNullConfigurationGiven(): void
    {
        $configuration = new CacheConfiguration();
        $readCommand = new RawCommand('GET', ['key'], CommandInterface::READ_MODE);

        $this->assertTrue($configuration->isWhitelistedCommand($readCommand));
        $this->assertSame(0, $configuration->getTTl());
        $this->assertFalse($configuration->isExceedsMaxSize(10000));
        $this->assertTrue($configuration->isExceedsMaxSize(10001));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedConfigurationKeyGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid cache configuration. Given keys are not expected: foo');

        new CacheConfiguration(['foo' => 'bar']);
    }
}
