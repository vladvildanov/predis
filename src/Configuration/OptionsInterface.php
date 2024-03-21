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

namespace Predis\Configuration;

use Predis\Command\Processor\ProcessorInterface;
use Predis\Connection\ParametersInterface;

/**
 * @property callable                            $aggregate      Custom aggregate connection initializer
 * @property bool                                $cache          Whether to use in-memory caching.
 * @property int                                 $cache_max_size Maximal numbers of items stored in a cache. Default: 10000
 * @property int                                 $cache_ttl      TTL for keys stored within a cache. Default: 0 (Never expires)
 * @property callable                            $cluster        Aggregate connection initializer for clustering
 * @property \Predis\Connection\FactoryInterface $connections    Connection factory for creating new connections
 * @property bool                                $exceptions     Toggles exceptions in client for -ERR responses
 * @property ProcessorInterface                  $prefix         Key prefixing strategy using the supplied string as prefix
 * @property int                                 $protocol       Version of RESP protocol.
 * @property \Predis\Command\FactoryInterface    $commands       Command factory for creating Redis commands
 * @property callable                            $replication    Aggregate connection initializer for replication
 * @property int                                 $readTimeout    Timeout in microseconds between read operations on reading from multiple connections.
 */
interface OptionsInterface
{
    /**
     * Returns the default value for the given option.
     *
     * @param string $option Name of the option
     *
     * @return mixed|null
     */
    public function getDefault($option);

    /**
     * Checks if the given option has been set by the user upon initialization.
     *
     * @param string $option Name of the option
     *
     * @return bool
     */
    public function defined($option);

    /**
     * Checks if the given option has been set and does not evaluate to NULL.
     *
     * @param string $option Name of the option
     *
     * @return bool
     */
    public function __isset($option);

    /**
     * Returns the value of the given option.
     *
     * @param string $option Name of the option
     *
     * @return mixed|null
     */
    public function __get($option);
}
