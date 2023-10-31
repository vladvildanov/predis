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

use Predis\Command\Command as RedisCommand;
use Predis\Command\CommandInterface;

/**
 * @see http://redis.io/commands/dbsize
 */
class DBSIZE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'DBSIZE';
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandMode(): string
    {
        return CommandInterface::READ_MODE;
    }
}
