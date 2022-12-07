<?php

namespace Predis\Command\Redis\RedisBloom;

use Predis\Client;
use Predis\Command\Redis\PredisCommandTestCase;

class BFADD_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BFADD::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'BF.ADD';
    }

    public function test(): void
    {
        $redis = new Client();
        $redis->bfadd(1);
    }
}
