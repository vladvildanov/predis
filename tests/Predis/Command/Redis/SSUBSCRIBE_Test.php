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

use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-pubsub
 */
class SSUBSCRIBE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SSUBSCRIBE::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SSUBSCRIBE';
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
    public function testSubscribesToGivenShardChannel(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['ssubscribe', 'key', 1], $redis->ssubscribe('key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSubscribesToMultipleShardChannels(): void
    {
        $redis = $this->getClient();

        $this->assertSame(['ssubscribe', 'key', 1], $redis->ssubscribe('key'));
        $this->assertSame(['ssubscribe', 'key1', 2], $redis->ssubscribe('key1'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testCannotSendOtherCommandsAfterSubscribe(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessageMatches('/ERR.*only .* allowed in this context/');

        $redis = $this->getClient();

        $redis->ssubscribe('channel:foo');
        $redis->set('foo', 'bar');
    }
}
