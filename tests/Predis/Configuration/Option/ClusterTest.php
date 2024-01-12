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

namespace Predis\Configuration\Option;

use Closure;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\RawCommand;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\Cache\CacheProxyConnection;
use Predis\Connection\Cluster\ClusterInterface;
use PredisTestCase;

class ClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Cluster\PredisCluster', $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperTriggersAggregationByDefault(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$parameters[0]],
                [$parameters[1]]
            )
            ->willReturnOnConsecutiveCalls(
                $nodeConnection1 = $this->getMockConnection($parameters[0]),
                $nodeConnection2 = $this->getMockConnection($parameters[1])
            );

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->exactly(2))
            ->method('__get')
            ->withConsecutive(['connections'], ['cache'])
            ->willReturnOnConsecutiveCalls($factory, false);

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$nodeConnection1],
                [$nodeConnection2]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, true));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperDoesNotTriggerAggregationWhenSecondArgumentIsFalse(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('cache')
            ->willReturn(false);

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, false));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperTriggersAggregationWhenSecondArgumentIsTrue(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$parameters[0]],
                [$parameters[1]]
            )
            ->willReturnOnConsecutiveCalls(
                $nodeConnection1 = $this->getMockConnection($parameters[0]),
                $nodeConnection2 = $this->getMockConnection($parameters[1])
            );

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->exactly(2))
            ->method('__get')
            ->withConsecutive(['connections'], ['cache'])
            ->willReturnOnConsecutiveCalls($factory, false);

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$nodeConnection1],
                [$nodeConnection2]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, true));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperDoesNotTriggerAggregationWhenFirstArgumentIsEmptyAndSecondArgumentIsTrue(): void
    {
        $option = new Cluster();
        $parameters = [];

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('cache')
            ->willReturn(false);

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, true));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches(
            '/^Predis\\\Configuration\\\Option\\\Cluster expects the supplied callable to return an instance of Predis\\\Connection\\\AggregateConnectionInterface, but .* was returned$/'
        );

        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));

        $initializer($parameters);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringPredis(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Cluster\PredisCluster', $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedis(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $options
            ->expects($this->exactly(4))
            ->method('__get')
            ->withConsecutive(
                ['connections'],
                ['crc16'],
                ['readTimeout'],
                ['cache']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock(),
                $this->getMockBuilder('Predis\Cluster\Hash\HashGeneratorInterface')->getMock(),
                1000,
                false
            );

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'redis'));
        $this->assertInstanceOf('Predis\Connection\Cluster\RedisCluster', $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedisCluster(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $options
            ->expects($this->exactly(4))
            ->method('__get')
            ->withConsecutive(
                ['connections'],
                ['crc16'],
                ['readTimeout'],
                ['cache']
            )
            ->willReturnOnConsecutiveCalls(
                $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock(),
                $this->getMockBuilder('Predis\Cluster\Hash\HashGeneratorInterface')->getMock(),
                1000,
                false
            );

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'redis-cluster'));
        $this->assertInstanceOf('Predis\Connection\Cluster\RedisCluster', $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testCreatesCacheProxyConnectionOnAnyCluster(): void
    {
        $option = new Cluster();
        $parameters = ['cache' => 1, 'cache_ttl' => 200];
        $mockConnection = $this->getMockBuilder(ClusterInterface::class)->getMock();

        $mockConnection
            ->expects($this->once())
            ->method('executeCommandOnEachNode')
            ->with(new RawCommand('CLIENT', ['TRACKING', 'ON']))
            ->willReturn(['OK']);

        $callback = function () use ($mockConnection) {
            return $mockConnection;
        };

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $options
            ->expects($this->once())
            ->method('__get')
            ->with('cache')
            ->willReturn(true);

        $options
            ->expects($this->once())
            ->method('getInput')
            ->willReturn($parameters);

        $this->assertInstanceOf(Closure::class, $initializer = $option->filter($options, $callback));
        $this->assertInstanceOf(
            CacheProxyConnection::class,
            $initializer($parameters)
        );
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Predis\Configuration\Option\Cluster expects either `predis`, `redis` or `redis-cluster` as valid string values, `unknown` given'
        );

        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInstanceOfClusterInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches(
            '/Predis\\\Configuration\\\Option\\\Cluster expects either a string or a callable value, .* given/'
        );

        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();

        $option->filter($options, $connection);
    }
}
