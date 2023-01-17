<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/spublish/
 *
 * Posts a message to the given shard channel.
 */
class SPUBLISH extends RedisCommand
{
    public function getId()
    {
        return 'SPUBLISH';
    }
}
