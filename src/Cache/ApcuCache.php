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

use APCUIterator;

class ApcuCache implements CacheWithMetadataInterface
{
    /**
     * @var APCUIterator
     */
    private $iterator;

    public function __construct(APCUIterator $iterator = null)
    {
        $this->iterator = $iterator ?? new APCUIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $key, $var, int $ttl = 0): bool
    {
        return apcu_add($key, $var, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function store(string $key, $var, int $ttl = 0): bool
    {
        return apcu_store($key, $var, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function batchAdd(array $dictionary, int $ttl = 0): array
    {
        return apcu_add($dictionary, null, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function batchStore(array $dictionary, int $ttl = 0): array
    {
        return apcu_store($dictionary, null, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $key)
    {
        return apcu_fetch($key);
    }

    /**
     * {@inheritDoc}
     */
    public function batchRead(array $keys)
    {
        return apcu_fetch($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function batchDelete(array $keys)
    {
        return apcu_delete($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $key): bool
    {
        return apcu_exists($key);
    }

    /**
     * {@inheritDoc}
     */
    public function batchExists(array $keys): array
    {
        return apcu_exists($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function flush(): bool
    {
        return apcu_clear_cache();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalSize(): int
    {
        return $this->iterator->getTotalSize();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalCount(): int
    {
        return $this->iterator->getTotalCount();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalHits(): int
    {
        return (int) apcu_cache_info(true)['num_hits'];
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalMisses(): int
    {
        return (int) apcu_cache_info(true)['num_misses'];
    }
}
