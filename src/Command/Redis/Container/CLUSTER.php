<?php

namespace Predis\Command\Redis\Container;

/**
 * @method string myShardId()
 */
class CLUSTER extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'cluster';
    }
}
