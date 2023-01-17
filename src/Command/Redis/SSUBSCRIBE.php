<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/ssubscribe/
 *
 * Subscribes the client to the specified shard channels.
 */
class SSUBSCRIBE extends RedisCommand
{
    public function getId()
    {
        return 'SSUBSCRIBE';
    }
}
