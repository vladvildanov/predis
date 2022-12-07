<?php

namespace Predis\Command\Redis\RedisBloom;

use Predis\Command\Command as RedisCommand;

class BFADD extends RedisCommand
{
    public function getId()
    {
        return "BF.ADD";
    }
}
