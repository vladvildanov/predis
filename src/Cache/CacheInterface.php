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

interface CacheInterface
{
    /**
     * Add key-value pair into cache if it doesn't exist yet.
     *
     * @param  string $key
     * @param         $var
     * @param  int    $ttl
     * @return bool
     */
    public function add(string $key, $var, int $ttl = 0): bool;

    /**
     * Add key or overwrite an existing key value.
     *
     * @param  string $key
     * @param         $var
     * @param  int    $ttl
     * @return bool
     */
    public function store(string $key, $var, int $ttl = 0): bool;

    /**
     * Batch version of add() operation.
     *
     * @param  array $dictionary Key-value pairs
     * @param  int   $ttl
     * @return array
     */
    public function batchAdd(array $dictionary, int $ttl = 0): array;

    /**
     * Batch version of store() operation.
     *
     * @param  array $dictionary Key-value pairs
     * @param  int   $ttl
     * @return array
     */
    public function batchStore(array $dictionary, int $ttl = 0): array;

    /**
     * Fetches value associated with given key.
     *
     * @param  string $key
     * @return mixed
     */
    public function read(string $key);

    /**
     * Fetches values associated with given keys.
     *
     * @param  array $keys
     * @return mixed
     */
    public function batchRead(array $keys);

    /**
     * Removes value associated with given key from cache.
     *
     * @param  string $key
     * @return mixed
     */
    public function delete(string $key): bool;

    /**
     * Removes values associated with given keys from cache.
     *
     * @param  array $keys
     * @return mixed
     */
    public function batchDelete(array $keys);

    /**
     * Checks if given key exists in the storage.
     *
     * @param  string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Checks if given keys exists in the storage.
     *
     * @param  array $keys
     * @return array
     */
    public function batchExists(array $keys): array;

    /**
     * Flushes cache.
     *
     * @return bool
     */
    public function flush(): bool;
}
