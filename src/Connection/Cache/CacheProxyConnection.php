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
use Predis\Command\RawCommand;
use Predis\Configuration\Cache\CacheConfiguration;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Traits\PushNotificationListener;
use Predis\Consumer\Push\PushNotificationException;
use Predis\Consumer\Push\PushResponseInterface;

class CacheProxyConnection implements ConnectionInterface
{
    use PushNotificationListener;

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
        $this->setupInvalidationTracking();
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
        return $this->connection->isConnected();
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
     * @throws PushNotificationException
     */
    public function executeCommand(CommandInterface $command)
    {
        $commandId = $command->getId();

        // 1. Check if given command is whitelisted.
        if (!$this->cacheConfiguration->isWhitelistedCommand($commandId)) {
            return $this->retryOnInvalidation($command);
        }

        // TODO: Enhance CommandInterface to provide a method that returns command key/keys.
        $key = $command->getArgument(0);
        $cacheKey = $commandId . '_' . $key;
        $ttl = $this->cacheConfiguration->getTTl();

        // 2. Returns cached data if key exists in cache.
        if (false !== $this->cache->exists($cacheKey)) {
            return $this->cache->read($cacheKey);
        }

        // 3. Cache response if it's allowed.
        if ($this->isAllowedToBeCached()) {
            $this->retryOnInvalidation(new RawCommand('CLIENT', ['CACHING', 'YES']));
            $response = $this->retryOnInvalidation($command);
            $this->cache->add($cacheKey, $response, $ttl);
        } else {
            $response = $this->retryOnInvalidation($command);
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
     * @return NodeConnectionInterface
     */
    public function getSentinelConnection(): NodeConnectionInterface
    {
        /* @phpstan-ignore-next-line */
        return $this->connection->getSentinelConnection();
    }

    /**
     * @return bool
     */
    private function isAllowedToBeCached(): bool
    {
        $totalCount = $this->cache->getTotalCount();

        if ($this->cacheConfiguration->isExceedsMaxCount($totalCount + 1)) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    private function setupInvalidationTracking(): void
    {
        $this->connection->executeCommand(new RawCommand('CLIENT', ['TRACKING', 'ON', 'OPTIN']));
        $this->onPushNotification([
            PushResponseInterface::INVALIDATE_DATA_TYPE => function (array $payload) {
                $invalidatedKeys = $payload[0];

                if (null === $invalidatedKeys) {
                    $this->cache->flush();

                    return;
                }

                foreach ($invalidatedKeys as $invalidatedKey) {
                    $invalidCommandResponses = $this->cache->findMatchingKeys("/$invalidatedKey/");
                    $this->cache->batchDelete($invalidCommandResponses);
                }
            },
        ]);
    }

    /**
     * Call dispatcher and retries read on push notification.
     *
     * @param  CommandInterface          $command
     * @return mixed
     * @throws PushNotificationException
     */
    protected function retryOnInvalidation(CommandInterface $command)
    {
        $response = $this->connection->executeCommand($command);

        if ($response instanceof PushResponseInterface) {
            do {
                $this->dispatchNotification($response);

                $response = $this->connection->readResponse($command);
            } while ($response instanceof PushResponseInterface);
        }

        return $response;
    }
}
