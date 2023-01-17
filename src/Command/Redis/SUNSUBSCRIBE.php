<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class SUNSUBSCRIBE extends RedisCommand
{
    public function getId()
    {
        return 'SUNSUBSCRIBE';
    }
}
