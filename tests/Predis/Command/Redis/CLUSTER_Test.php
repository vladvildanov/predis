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

class CLUSTER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CLUSTER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CLUSTER';
    }

    /**
     * @group disconnected
     */
    public function testMyShardIdFilterArguments(): void
    {
        $arguments = $expected = ['MYSHARID'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(10, $this->getCommand()->parseResponse(10));
    }

    /**
     * @group connected
     * @group cluster
     * @return void
     * @requiresRedisVersion >= 7.2.0
     */
    public function testMyShardIdReturnsUniqueShardIdentifier(): void
    {
        $redis = $this->getClient();

        $this->assertNotEmpty($redis->cluster->myShardId());
    }
}
