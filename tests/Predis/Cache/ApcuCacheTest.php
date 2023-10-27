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

namespace Predis\Cache;

use PredisTestCase;

/**
 * @group ext-apcu
 */
class ApcuCacheTest extends PredisTestCase
{
    /**
     * @var ApcuCache
     */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = new ApcuCache();
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @return void
     */
    public function testAddKeyIntoCache(): void
    {
        $this->assertTrue($this->cache->add('foo', 'bar'));
    }

    /**
     * @return void
     */
    public function testDoNotAddKeyIntoCacheIfAlreadyExists(): void
    {
        $this->assertTrue($this->cache->add('foo', 'bar'));
        $this->assertFalse($this->cache->add('foo', 'bar'));
    }

    /**
     * @return void
     */
    public function testStoresValueWithinGivenKey(): void
    {
        $this->assertTrue($this->cache->store('foo', 'bar'));
        $this->assertTrue($this->cache->store('foo', 'baz'));
        $this->assertSame('baz', $this->cache->read('foo'));
    }

    /**
     * @return void
     */
    public function testBatchAddMultipleKeyValuesIntoCache(): void
    {
        $this->assertSame([], $this->cache->batchAdd(['foo' => 'bar', 'bar' => 'foo']));
    }

    /**
     * @return void
     */
    public function testBatchStoresMultipleKeyValuesIntoCache(): void
    {
        $this->assertSame([], $this->cache->batchStore(['foo' => 'bar', 'bar' => 'foo']));
    }

    /**
     * @return void
     */
    public function testReadsValueFromCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertTrue($this->cache->add('array', ['val1', 'val2']));
        $this->assertSame('value', $this->cache->read('string'));
        $this->assertSameValues(['val1', 'val2'], $this->cache->read('array'));
    }

    /**
     * @return void
     */
    public function testBatchReadsMultipleValuesFromCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertTrue($this->cache->add('array', ['val1', 'val2']));
        $this->assertSame(
            ['string' => 'value', 'array' => ['val1', 'val2']],
            $this->cache->batchRead(['string', 'array'])
        );
    }

    /**
     * @return void
     */
    public function testDeletesFromCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertTrue($this->cache->delete('string'));
    }

    /**
     * @return void
     */
    public function testBatchDeletesMultipleValuesFromCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertTrue($this->cache->add('array', ['val1', 'val2']));
        $this->assertSame([], $this->cache->batchDelete(['string', 'array']));
    }

    /**
     * @return void
     */
    public function testExistsKeyInLocalCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertTrue($this->cache->exists('string'));
        $this->assertFalse($this->cache->exists('foo'));
    }

    /**
     * @return void
     */
    public function testBatchExistsMultipleKeysInLocalCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertTrue($this->cache->add('array', ['val1', 'val2']));
        $this->assertSameValues(['string' => true, 'array' => true], $this->cache->batchExists(['string', 'array']));
    }

    /**
     * @return void
     */
    public function testFlushLocalCache(): void
    {
        $this->assertTrue($this->cache->flush());
    }

    /**
     * @return void
     */
    public function testGetTotalSizeOfLocalCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertSame(160, $this->cache->getTotalSize());
    }

    /**
     * @return void
     */
    public function testGetTotalCountOfLocalCachedRecords(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertSame(1, $this->cache->getTotalCount());
    }

    /**
     * @return void
     */
    public function testGetTotalHitsOfLocalCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertSame(0, $this->cache->getTotalHits());

        $this->cache->read('string');
        $this->cache->read('foo');

        $this->assertSame(1, $this->cache->getTotalHits());
    }

    /**
     * @return void
     */
    public function testGetTotalMissesOfLocalCache(): void
    {
        $this->assertTrue($this->cache->add('string', 'value'));
        $this->assertSame(0, $this->cache->getTotalMisses());

        $this->cache->read('foo');

        $this->assertSame(1, $this->cache->getTotalMisses());
    }

    /**
     * @return void
     */
    public function testFindMatchingKeys(): void
    {
        $this->assertTrue($this->cache->add('key_foo', 'value'));
        $this->assertTrue($this->cache->add('key_bar', 'value'));
        $this->assertTrue($this->cache->add('key_foo_bar', 'value'));

        $this->assertSame(['key_foo', 'key_foo_bar'], $this->cache->findMatchingKeys('/foo/'));
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
    }
}
