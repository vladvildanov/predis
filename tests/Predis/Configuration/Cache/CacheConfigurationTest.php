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
        $configuration = new CacheConfiguration(['ttl' => 100]);

        $this->assertSame(100, $configuration->getTTl());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testIsWhitelistedCommand(): void
    {
        $configuration = new CacheConfiguration();

        $this->assertTrue($configuration->isWhitelistedCommand('GET'));
        $this->assertFalse($configuration->isWhitelistedCommand('HGET'));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testIsExceedsMaxCount(): void
    {
        $configuration = new CacheConfiguration(['max_count' => 2]);

        $this->assertFalse($configuration->isExceedsMaxCount(2));
        $this->assertTrue($configuration->isExceedsMaxCount(3));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testReturnsDefaultConfigurationOnNullConfigurationGiven(): void
    {
        $configuration = new CacheConfiguration();

        $this->assertTrue($configuration->isWhitelistedCommand('GET'));
        $this->assertSame(0, $configuration->getTTl());
        $this->assertFalse($configuration->isExceedsMaxCount(1000));
        $this->assertTrue($configuration->isExceedsMaxCount(1001));
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
