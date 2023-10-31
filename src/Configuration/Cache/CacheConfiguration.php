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

use Closure;
use Predis\Command\CommandInterface;
use UnexpectedValueException;

class CacheConfiguration
{
    /**
     * Maximum records count to store in a cache.
     *
     * @var int
     */
    private $maxCount;

    /**
     * Time-to-live in seconds for the record stored in a cache.
     *
     * @var int
     */
    private $ttl;

    /**
     * Callback to define if given command response is allowed to be cached.
     *
     * @var Closure
     */
    private $whitelistCallback;

    /**
     * Mapping for expected array keys to object properties.
     *
     * @var array
     */
    private $propertiesMapping = [
        'max_count' => 'maxCount',
        'ttl' => 'ttl',
    ];

    /**
     * This commands responses should be excluded from caching.
     *
     * @var array
     */
    private $excludedCommands = [
        'FT.AGGREGATE', 'FT.ALIASADD', 'FT.ALIASDEL', 'FT.ALIASUPDATE', 'FT.CURSOR', 'FT.EXPLAIN', 'FT.EXPLAINCLI', 'FT.GET', 'FT.INFO', 'FT.MGET',
        'FT.PROFILE', 'FT.SEARCH', 'FT.SPELLCHECK', 'FT.SUGGET', 'FT.SUGLEN', 'FT.SYNDUMP', 'FT.TAGVALS', 'FT._ALIASADDIFNX', 'BF.CARD', 'BF.DEBUG',
        'BF.EXISTS', 'BF.INFO', 'BF.MEXISTS', 'BF.SCANDUMP','CF.COMPACT', 'CF.COUNT', 'CF.DEBUG', 'CF.EXISTS', 'CF.INFO', 'CF.MEXISTS', 'CF.SCANDUMP',
        'CMS.INFO', 'CMS.QUERY', 'EXPIRETIME', 'HRANDFIELD', 'JSON.DEBUG', 'PEXPIRETIME', 'PFCOUNT', 'PTTL', 'SRANDMEMBER', 'TDIGEST.BYRANK',
        'TDIGEST.BYREVRANK', 'TDIGEST.CDF', 'TDIGEST.INFO', 'TDIGEST.MAX', 'TDIGEST.MIN', 'TDIGEST.QUANTILE', 'TDIGEST.RANK', 'TDIGEST.REVRANK',
        'TDIGEST.TRIMMED_MEAN', 'TOPK.INFO', 'TOPK.LIST', 'TOPK.QUERY', 'TTL'
    ];

    public function __construct(array $configuration = null)
    {
        $this->setDefaultConfiguration();

        if (null !== $configuration) {
            $this->mapConfiguration($configuration);
        }
    }

    /**
     * @return int
     */
    public function getTTl(): int
    {
        return $this->ttl;
    }

    /**
     * @param  CommandInterface $command
     * @return bool
     */
    public function isWhitelistedCommand(CommandInterface $command): bool
    {
        $callback = $this->whitelistCallback;

        return $callback($command);
    }

    /**
     * Check if given count exceeds max count threshold.
     *
     * @param  int  $count
     * @return bool
     */
    public function isExceedsMaxCount(int $count): bool
    {
        return $count > $this->maxCount;
    }

    /**
     * Map given configuration to object properties.
     *
     * @param  array|null $configuration
     * @return void
     */
    private function mapConfiguration(array $configuration = null): void
    {
        $unexpectedKeys = array_filter(array_keys($configuration), function ($key) {
            return !array_key_exists($key, $this->propertiesMapping);
        });

        if (!empty($unexpectedKeys)) {
            $unexpectedKeysString = implode(', ', $unexpectedKeys);
            throw new UnexpectedValueException("Invalid cache configuration. Given keys are not expected: {$unexpectedKeysString}");
        }

        foreach ($configuration as $key => $value) {
            $property = $this->propertiesMapping[$key];
            $this->$property = (int) $value;
        }
    }

    /**
     * @return void
     */
    private function setDefaultConfiguration(): void
    {
        $this->maxCount = 1000;
        $this->ttl = 0;
        $this->whitelistCallback = $this->getDefaultWhitelistCallback();
    }

    /**
     * @return Closure
     */
    private function getDefaultWhitelistCallback(): Closure
    {
        return function (CommandInterface $command): bool {
            return $command->getCommandMode() === CommandInterface::READ_MODE
                && !in_array($command->getId(), $this->excludedCommands, true);
        };
    }
}
