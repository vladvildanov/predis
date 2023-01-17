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
class SPUBLISH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SPUBLISH::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SPUBLISH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['shardChannel', 'message'];
        $expected = ['shardChannel', 'message'];

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
    public function testPublishMessageOnChannelWithNoSubscribers(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->spublish('channel', 'message'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testPublishMessageOnChannelWithOneSubscriber(): void
    {
        $subscriber = $this->getClient();
        $subscriber->ssubscribe('channel');

        $producer = $this->getClient();

        $this->assertSame(1, $producer->spublish('channel', 'message'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testPublishMessageOnChannelWithMultipleSubscribers(): void
    {
        $subscriber1 = $this->getClient();
        $subscriber1->ssubscribe('channel');

        $subscriber2 = $this->getClient();
        $subscriber2->ssubscribe('channel');

        $subscriber3 = $this->getClient();
        $subscriber3->ssubscribe('channel');

        $producer = $this->getClient();

        $this->assertSame(3, $producer->spublish('channel', 'message'));
    }
}
