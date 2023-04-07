<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/?name=CLUSTER
 *
 * Container command corresponds to any CLUSTER *.
 * Represents any CLUSTER command with subcommand as first argument.
 */
class CLUSTER extends RedisCommand
{
    public function getId()
    {
        return 'CLUSTER';
    }
}
