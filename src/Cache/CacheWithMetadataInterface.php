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

interface CacheWithMetadataInterface extends CacheInterface
{
    /**
     * Retrieves total cache memory size.
     *
     * @return int
     */
    public function getTotalSize(): int;

    /**
     * Retrieves total cached records count.
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Retrieves total cache hits count.
     *
     * @return int
     */
    public function getTotalHits(): int;

    /**
     * Retrieves total cache misses count.
     *
     * @return int
     */
    public function getTotalMisses(): int;
}
