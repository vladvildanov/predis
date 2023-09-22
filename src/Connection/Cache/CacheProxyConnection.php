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

namespace Predis\Connection\Cache;

use Predis\Cache\CacheWithMetadataInterface;
use Predis\Command\CommandInterface;
use Predis\Configuration\Cache\CacheConfiguration;
use Predis\Connection\ConnectionInterface;

class CacheProxyConnection implements ConnectionInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var CacheConfiguration
     */
    private $cacheConfiguration;

    /**
     * @var CacheWithMetadataInterface
     */
    private $cache;

    public function __construct(
        ConnectionInterface $connection,
        CacheConfiguration $cacheConfiguration,
        CacheWithMetadataInterface $cache
    ) {
        $this->connection = $connection;
        $this->cacheConfiguration = $cacheConfiguration;
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        $this->connection->connect();
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect()
    {
        $this->connection->disconnect();
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {
        $this->connection->isConnected();
    }

    /**
     * {@inheritDoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->connection->writeRequest($command);
    }

    /**
     * {@inheritDoc}
     */
    public function readResponse(CommandInterface $command)
    {
        $this->connection->readResponse($command);
    }

    /**
     * {@inheritDoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $whitelistCallback = $this->cacheConfiguration->getWhitelistCallback();
        $commandId = $command->getId();

        // 1. Check if given command is whitelisted.
        if (!$whitelistCallback($commandId)) {
            return $this->connection->executeCommand($command);
        }

        // TODO: Enhance CommandInterface to provide a method that returns command key/keys.
        $key = $command->getArgument(0);
        $cacheKey = $commandId . '_' . $key;
        $ttl = $this->cacheConfiguration->getTTl();

        // 2. Returns cached data if key exists in cache.
        if (false !== $this->cache->exists($cacheKey)) {
            return $this->cache->read($cacheKey);
        }

        $response = $this->connection->executeCommand($command);

        // 3. Cache response if it's allowed.
        if ($this->isAllowedToBeCached()) {
            $this->cache->add($cacheKey, $response, $ttl);
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters()
    {
        return $this->connection->getParameters();
    }

    /**
     * @return bool
     */
    private function isAllowedToBeCached(): bool
    {
        $totalCount = $this->cache->getTotalCount();

        // Check if max count threshold is exceeded.
        if ($this->cacheConfiguration->isExceedsMaxCount($totalCount + 1)) {
            return false;
        }

        return true;
    }
}
