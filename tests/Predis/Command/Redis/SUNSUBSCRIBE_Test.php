<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-pubsub
 */
class SUNSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SUNSUBSCRIBE::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SUNSUBSCRIBE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['shardChannel'];
        $expected = ['shardChannel'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testUnsubscribesFromNotSubscribedChannel(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['sunsubscribe', 'channel', 0], $redis->sunsubscribe('channel'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testUnsubscribesFromAlreadySubscribedChannel(): void
    {
        $redis = $this->getClient();

        $redis->ssubscribe('key');
        $redis->ssubscribe('key1');

        $this->assertSame(['sunsubscribe', 'key', 1], $redis->sunsubscribe('key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testUnsubscribesFromAllSubscribedChannels(): void
    {
        $redis = $this->getClient();

        $redis->ssubscribe('key');
        $redis->ssubscribe('key1');

        $this->assertSame(['sunsubscribe', 'key1', 1], $redis->sunsubscribe());
        $this->assertSame(['sunsubscribe', 'key', 0], $redis->getConnection()->read());
    }
}
