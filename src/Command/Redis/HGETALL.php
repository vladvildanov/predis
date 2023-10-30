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

namespace Predis\Command\Redis;

use Predis\Command\CommandInterface;
use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see http://redis.io/commands/hgetall
 */
class HGETALL extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HGETALL';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if ($data !== array_values($data)) {
            return $data; // Relay
        }

        $result = [];

        for ($i = 0; $i < count($data); ++$i) {
            $result[$data[$i]] = $data[++$i];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandMode(): string
    {
        return CommandInterface::READ_MODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(): array
    {
        return [$this->getArgument(0)];
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
