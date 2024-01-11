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

namespace Predis;

use Iterator;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\CommandInterface;
use Predis\Command\Factory as CommandFactory;
use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Command\Redis\BITCOUNT;
use Predis\Command\Redis\GEOADD;
use Predis\Command\Redis\GEODIST;
use Predis\Command\Redis\HGET;
use Predis\Command\Redis\HSET;
use Predis\Command\Redis\Json\JSONGET;
use Predis\Command\Redis\Json\JSONSET;
use Predis\Command\Redis\LINDEX;
use Predis\Command\Redis\LPUSH;
use Predis\Command\Redis\LSET;
use Predis\Command\Redis\MGET;
use Predis\Command\Redis\MSET;
use Predis\Command\Redis\SADD;
use Predis\Command\Redis\SET;
use Predis\Command\Redis\SMEMBERS;
use Predis\Command\Redis\TimeSeries\TSADD;
use Predis\Command\Redis\TimeSeries\TSGET;
use Predis\Command\Redis\XADD;
use Predis\Command\Redis\XLEN;
use Predis\Command\Redis\ZADD;
use Predis\Command\Redis\ZCARD;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Connection\ParametersInterface;
use Predis\Connection\Replication\MasterSlaveReplication;
use PredisTestCase;
use ReflectionProperty;
use stdClass;

class ClientTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithoutArguments(): void
    {
        $client = new Client();

        /** @var NodeConnectionInterface */
        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->commands, $client->getCommandFactory());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullArgument(): void
    {
        $client = new Client(null);

        /** @var NodeConnectionInterface */
        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->commands, $client->getCommandFactory());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndNullArguments(): void
    {
        $client = new Client(null, null);

        /** @var NodeConnectionInterface */
        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->commands, $client->getCommandFactory());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayArgument(): void
    {
        $client = new Client($arg1 = ['host' => 'localhost', 'port' => 7000]);

        /** @var NodeConnectionInterface */
        $connection = $client->getConnection();
        $parameters = $connection->getParameters();

        $this->assertSame($parameters->host, $arg1['host']);
        $this->assertSame($parameters->port, $arg1['port']);
    }

    /**
     * @group disconnected
     */
    public function testConstructorThrowsExceptionWithArrayOfParametersArgumentAndMissingOption(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Array of connection parameters requires `cluster`, `replication` or `aggregate` client option');

        $arg1 = [
            ['host' => 'localhost', 'port' => 7000],
            ['host' => 'localhost', 'port' => 7001],
        ];

        $client = new Client($arg1);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfArrayArgumentAndClusterOption(): void
    {
        $arg1 = [
            ['host' => 'localhost', 'port' => 7000],
            ['host' => 'localhost', 'port' => 7001],
        ];

        $client = new Client($arg1, [
            'aggregate' => $this->getAggregateInitializer($arg1),
        ]);

        $this->assertInstanceOf('Predis\Connection\AggregateConnectionInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithStringArgument(): void
    {
        $client = new Client('tcp://localhost:7000');

        /** @var NodeConnectionInterface */
        $connection = $client->getConnection();
        $parameters = $connection->getParameters();

        $this->assertSame($parameters->host, 'localhost');
        $this->assertSame($parameters->port, 7000);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfStringArgument(): void
    {
        $arg1 = ['tcp://localhost:7000', 'tcp://localhost:7001'];

        $client = new Client($arg1, [
            'aggregate' => $this->getAggregateInitializer($arg1),
        ]);

        $this->assertInstanceOf('Predis\Connection\AggregateConnectionInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfConnectionsArgument(): void
    {
        $arg1 = [
            $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock(),
            $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock(),
        ];

        $client = new Client($arg1, [
            'aggregate' => $this->getAggregateInitializer($arg1),
        ]);

        $this->assertInstanceOf('Predis\Connection\AggregateConnectionInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithConnectionArgument(): void
    {
        $factory = new Connection\Factory();
        $connection = $factory->create('tcp://localhost:7000');

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $client->getConnection());
        $this->assertSame($connection, $client->getConnection());

        /** @var NodeConnectionInterface */
        $connection = $client->getConnection();
        $parameters = $connection->getParameters();

        $this->assertSame($parameters->host, 'localhost');
        $this->assertSame($parameters->port, 7000);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithClusterArgument(): void
    {
        $cluster = new Connection\Cluster\PredisCluster(new Parameters());

        $factory = new Connection\Factory();
        $cluster->add($factory->create('tcp://localhost:7000'));
        $cluster->add($factory->create('tcp://localhost:7001'));

        $client = new Client($cluster);

        $this->assertInstanceOf('Predis\Connection\Cluster\ClusterInterface', $client->getConnection());
        $this->assertSame($cluster, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithReplicationArgument(): void
    {
        $replication = new Connection\Replication\MasterSlaveReplication();

        $factory = new Connection\Factory();
        $replication->add($factory->create('tcp://host1?alias=master'));
        $replication->add($factory->create('tcp://host2?alias=slave'));

        $client = new Client($replication);

        $this->assertInstanceOf('Predis\Connection\Replication\ReplicationInterface', $client->getConnection());
        $this->assertSame($replication, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCallableArgument(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($connection);

        $client = new Client($callable);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCallableConnectionInitializerThrowsExceptionOnInvalidReturnType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Callable parameters must return a valid connection');

        $wrongType = $this->getMockBuilder('stdClass')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($wrongType);

        new Client($callable);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndArrayArgument(): void
    {
        $connections = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $arg2 = ['prefix' => 'prefix:', 'connections' => $connections];
        $client = new Client(null, $arg2);

        /** @var CommandFactory */
        $commands = $client->getCommandFactory();
        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);

        /** @var KeyPrefixProcessor */
        $processor = $commands->getProcessor();
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $processor);
        $this->assertSame('prefix:', $processor->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayAndOptionReplication(): void
    {
        $arg1 = ['tcp://127.0.0.1:6379?role=master', 'tcp://127.0.0.1:6380?role=slave'];
        $arg2 = ['replication' => 'predis'];
        $client = new Client($arg1, $arg2);

        /** @var MasterSlaveReplication */
        $connection = $client->getConnection();

        $this->assertInstanceOf('Predis\Connection\Replication\ReplicationInterface', $connection);
        $this->assertSame('127.0.0.1:6379', (string) $connection->getConnectionByRole('master'));
        $this->assertSame('127.0.0.1:6380', (string) $connection->getConnectionByRole('slave'));
    }

    /**
     * @group disconnected
     */
    public function testClusterOptionHasPrecedenceOverReplicationOptionAndAggregateOption(): void
    {
        $arg1 = ['tcp://host1', 'tcp://host2'];

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $fncluster = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $fncluster
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $arg1,
                $this->isInstanceOf('Predis\Configuration\OptionsInterface'),
                $this->isInstanceOf('Predis\Configuration\OptionInterface')
            )
            ->willReturn($connection);

        $fnreplication = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $fnreplication
            ->expects($this->never())
            ->method('__invoke');

        $fnaggregate = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $fnaggregate
            ->expects($this->never())
            ->method('__invoke');

        $arg2 = [
            'cluster' => $fncluster,
            'replication' => $fnreplication,
            'aggregate' => $fnaggregate,
        ];

        $client = new Client($arg1, $arg2);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testReplicationOptionHasPrecedenceOverAggregateOption(): void
    {
        $arg1 = ['tcp://host1', 'tcp://host2'];

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $fnreplication = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $fnreplication
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $arg1,
                $this->isInstanceOf('Predis\Configuration\OptionsInterface'),
                $this->isInstanceOf('Predis\Configuration\OptionInterface')
            )
            ->willReturn($connection);

        $fnaggregate = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $fnaggregate
            ->expects($this->never())
            ->method('__invoke');

        $arg2 = [
            'replication' => $fnreplication,
            'aggregate' => $fnaggregate,
        ];

        $client = new Client($arg1, $arg2);
    }

    /**
     * @group disconnected
     */
    public function testAggregateOptionDoesNotTriggerAggregationInClient(): void
    {
        $arg1 = ['tcp://host1', 'tcp://host2'];

        $connections = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $connections
            ->expects($this->never())
            ->method('create');

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $fnaggregate = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $fnaggregate
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $arg1,
                $this->isInstanceOf('Predis\Configuration\OptionsInterface'),
                $this->isInstanceOf('Predis\Configuration\OptionInterface')
            )
            ->willReturn($connection);

        $arg2 = ['aggregate' => $fnaggregate, 'connections' => $connections];

        $client = new Client($arg1, $arg2);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithInvalidArgumentType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid type for connection parameters');

        $client = new Client(new stdClass());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithInvalidOptionType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid type for client options');

        $client = new Client('tcp://host1', new stdClass());
    }

    /**
     * @group disconnected
     */
    public function testConnectAndDisconnect(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('connect');
        $connection
            ->expects($this->once())
            ->method('disconnect');

        $client = new Client($connection);
        $client->connect();
        $client->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedChecksConnectionState(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('isConnected');

        $client = new Client($connection);
        $client->isConnected();
    }

    /**
     * @group disconnected
     */
    public function testQuitIsAliasForDisconnect(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('disconnect');

        $client = new Client($connection);
        $client->quit();
    }

    /**
     * @group disconnected
     */
    public function testCreatesNewCommandUsingSpecifiedCommandFactory(): void
    {
        $ping = $this->getCommandFactory()->create('ping', []);

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->once())
            ->method('create')
            ->with('ping', [])
            ->willReturn($ping);

        $client = new Client(null, ['commands' => $commands]);
        $this->assertSame($ping, $client->createCommand('ping', []));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandReturnsParsedResponses(): void
    {
        $commands = $this->getCommandFactory();

        $ping = $commands->create('ping', []);
        $hgetall = $commands->create('hgetall', ['metavars', 'foo', 'hoge']);

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$ping],
                [$hgetall]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('PONG'),
                ['foo', 'bar', 'hoge', 'piyo']
            );

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $client = new Client($connection);

        $this->assertEquals('PONG', $client->executeCommand($ping));
        $this->assertSame(['foo' => 'bar', 'hoge' => 'piyo'], $client->executeCommand($hgetall));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandReturnsResp3ParsedResponses(): void
    {
        $commands = $this->getCommandFactory();

        $ping = $commands->create('ping', []);
        $get = $commands->create('get', []);

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$ping],
                [$get]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('PONG'),
                []
            );

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 3]));

        $client = new Client($connection);

        $this->assertEquals('PONG', $client->executeCommand($ping));
        $this->assertSame([], $client->executeCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandThrowsExceptionOnRedisError(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $ping = $this->getCommandFactory()->create('ping', []);
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn($expectedResponse);

        $client = new Client($connection);
        $client->executeCommand($ping);
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandReturnsErrorResponseOnRedisError(): void
    {
        $ping = $this->getCommandFactory()->create('ping', []);
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn($expectedResponse);

        $client = new Client($connection, ['exceptions' => false]);
        $response = $client->executeCommand($ping);

        $this->assertSame($response, $expectedResponse);
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandExecutesInstanceOfCommand(): void
    {
        $ping = $this->getCommandFactory()->create('ping', []);

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isInstanceOf('Predis\Command\Redis\PING'))
            ->willReturn('PONG');

        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->once())
            ->method('create')
            ->with('ping', [])
            ->willReturn($ping);

        $options = ['commands' => $commands];

        /** @var ClientInterface */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods([])
            ->setConstructorArgs([$connection, $options])
            ->getMock();

        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandThrowsExceptionOnServerError(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->willReturn($expectedResponse);

        $client = new Client($connection);
        $client->ping();
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandReturnsErrorResponseOnRedisError(): void
    {
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->willReturn($expectedResponse);

        $client = new Client($connection, ['exceptions' => false]);
        $response = $client->ping();

        $this->assertSame($response, $expectedResponse);
    }

    /**
     * @group disconnected
     */
    public function testRawCommand(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(3))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('SET', ['foo', 'bar'])],
                [$this->isRedisCommand('GET', ['foo'])],
                [$this->isRedisCommand('PING')]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                'bar',
                'PONG'
            );

        $client = new Client($connection);

        $this->assertSame('OK', $client->executeRaw(['SET', 'foo', 'bar']));
        $this->assertSame('bar', $client->executeRaw(['GET', 'foo']));

        $error = true;  // $error is always populated by reference.
        $this->assertSame('PONG', $client->executeRaw(['PING'], $error));
        $this->assertFalse($error);
    }

    /**
     * @group disconnected
     */
    public function testRawCommandNeverAppliesPrefix(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('SET', ['foo', 'bar'])],
                [$this->isRedisCommand('GET', ['foo'])]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                'bar'
            );

        $client = new Client($connection, ['prefix' => 'predis:']);

        $this->assertSame('OK', $client->executeRaw(['SET', 'foo', 'bar']));
        $this->assertSame('bar', $client->executeRaw(['GET', 'foo']));
    }

    /**
     * @group disconnected
     */
    public function testRawCommandNeverThrowsExceptions(): void
    {
        $message = 'ERR Mock error response';
        $response = new Response\Error($message);

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->willReturn($response);

        $client = new Client($connection, ['exceptions' => true]);

        $this->assertSame($message, $client->executeRaw(['PING'], $error));
        $this->assertTrue($error);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonRegisteredRedisCommand(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('Command `INVALIDCOMMAND` is not a registered Redis command');

        $client = new Client();
        $client->invalidCommand();
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodReturnsInstanceOfSubclass(): void
    {
        /** @var Client */
        $client = $this->getMockBuilder('Predis\Client')
            ->onlyMethods([])
            ->setConstructorArgs([
                ['tcp://host1?alias=node01', 'tcp://host2?alias=node02'],
                ['cluster' => 'predis'],
            ])
            ->setMockClassName('SubclassedClient')
            ->getMock();

        $this->assertInstanceOf('SubclassedClient', $client->getClientBy('alias', 'node02'));
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionById(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->onlyMethods(['getConnectionById'])
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionById')
            ->with('127.0.0.1:6379')
            ->willReturn($connection);

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('id', '127.0.0.1:6379');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodThrowsExceptionSelectingConnectionByUnknownId(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Cannot find a connection by id matching `127.0.0.1:7000`');

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionById')
            ->with('127.0.0.1:7000')
            ->willReturn(null);

        $client = new Client($aggregate);
        $client->getClientBy('id', '127.0.0.1:7000');
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByAlias(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->addMethods(['getConnectionByAlias'])
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByAlias')
            ->with('myalias')
            ->willReturn($connection);

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('alias', 'myalias');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByKey(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->addMethods(['getConnectionByKey'])
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByKey')
            ->with('key:1')
            ->willReturn($connection);

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('key', 'key:1');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionBySlot(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->addMethods(['getConnectionBySlot'])
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionBySlot')
            ->with(5460)
            ->willReturn($connection);

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('slot', 5460);

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByRole(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->addMethods(['getConnectionByRole'])
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByRole')
            ->with('master')
            ->willReturn($connection);

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('role', 'master');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByCommand(): void
    {
        $command = Command\RawCommand::create('GET', 'key');
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->onlyMethods(['getConnectionByCommand'])
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByCommand')
            ->with($command)
            ->willReturn($connection);

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('command', $command);

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodThrowsExceptionWhenSelectingConnectionByUnknownType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid selector type: `unknown`');

        $client = new Client('tcp://127.0.0.1?alias=node01');

        $client->getClientBy('unknown', 'test');
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodThrowsExceptionWhenConnectionDoesNotSupportSelectorType(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Selecting connection by id is not supported by Predis\Connection\StreamConnection');

        $client = new Client('tcp://127.0.0.1?alias=node01');

        $client->getClientBy('id', 'node01');
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithoutArgumentsReturnsPipeline(): void
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Pipeline\Pipeline', $client->pipeline());
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithArrayReturnsPipeline(): void
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Pipeline\Pipeline', $client->pipeline([]));
        $this->assertInstanceOf('Predis\Pipeline\Atomic', $client->pipeline(['atomic' => true]));
        $this->assertInstanceOf('Predis\Pipeline\FireAndForget', $client->pipeline(['fire-and-forget' => true]));
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithCallableExecutesPipeline(): void
    {
        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Pipeline\Pipeline'));

        $client = new Client();
        $client->pipeline($callable);
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithoutArgumentsReturnsPubSubConsumer(): void
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Consumer\PubSub\Consumer', $client->pubSubLoop());
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithArrayReturnsPubSubConsumerWithOptions(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $options = ['subscribe' => 'channel'];

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Consumer\PubSub\Consumer', $pubsub = $client->pubSubLoop($options));

        $reflection = new ReflectionProperty($pubsub, 'options');
        $reflection->setAccessible(true);

        $this->assertSame($options, $reflection->getValue($pubsub));
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithArrayAndCallableExecutesPubSub(): void
    {
        // NOTE: we use a subscribe count of 0 in the message payload to trick
        //       the context and forcing it to be closed since there are no more
        //       active subscriptions.
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->willReturn(['subscribe', 'channel', 0]);

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke');

        $client = new Client($connection);
        $this->assertNull($client->pubSubLoop(['subscribe' => 'channel'], $callable));
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithCallableReturningFalseStopsPubSubConsumer(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                ['subscribe', 'channel', 1],
                ['unsubscribe', 'channel', 0]
            );
        $connection
            ->expects($this->exactly(2))
            ->method('writeRequest')
            ->withConsecutive(
                [$this->isRedisCommand('SUBSCRIBE')],
                [$this->isRedisCommand('UNSUBSCRIBE')]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [
                    $this->isInstanceOf('Predis\Consumer\PubSub\Consumer'),
                    (object) ['kind' => 'subscribe', 'channel' => 'channel', 'payload' => 1],
                ],
                [
                    $this->isInstanceOf('Predis\Consumer\PubSub\Consumer'),
                    (object) ['kind' => 'unsubscribe', 'channel' => 'channel', 'payload' => 0],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                null // <-- this value would be ignored as it is the callback to UNSUBSCRIBE
            );

        $client = new Client($connection);

        $this->assertNull($client->pubSubLoop(['subscribe' => 'channel'], $callable));
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithoutArgumentsReturnsMultiExec(): void
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Transaction\MultiExec', $client->transaction());
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithArrayReturnsMultiExecTransactionWithOptions(): void
    {
        $options = ['cas' => true, 'retry' => 3];

        $client = new Client();

        $this->assertInstanceOf('Predis\Transaction\MultiExec', $tx = $client->transaction($options));

        // I hate this part but reflection is the easiest way in this case.
        $property = new ReflectionProperty($tx, 'modeCAS');
        $property->setAccessible(true);
        $this->assertSame($options['cas'], $property->getValue($tx));

        $property = new ReflectionProperty($tx, 'attempts');
        $property->setAccessible(true);
        $this->assertSame($options['retry'], $property->getValue($tx));
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithArrayAndCallableExecutesMultiExec(): void
    {
        // We use CAS here as we don't care about the actual MULTI/EXEC context.
        $options = ['cas' => true, 'retry' => 3];

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->willReturn(new Response\Status('QUEUED'));

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function ($tx) { $tx->ping(); });

        $client = new Client($connection);
        $client->transaction($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testMonitorReturnsMonitorConsumer(): void
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Monitor\Consumer', $monitor = $client->monitor());
    }

    /**
     * @group disconnected
     */
    public function testClientResendScriptCommandUsingEvalOnNoScriptErrors(): void
    {
        $luaScriptBody = 'return redis.call(\'exists\', KEYS[1])';

        $command = $this->getMockForAbstractClass('Predis\Command\ScriptCommand', [], '', true, true, true, ['parseResponse']);
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn($luaScriptBody);
        $command
            ->expects($this->once())
            ->method('parseResponse')
            ->with('OK')
            ->willReturn(true);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$command],
                [$this->isRedisCommand('EVAL', [$luaScriptBody])]
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Error('NOSCRIPT'),
                'OK'
            );

        $connection
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturn(new Parameters(['protocol' => 2]));

        $client = new Client($connection);

        $this->assertTrue($client->executeCommand($command));
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorWithTraversableConnections(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383');

        $aggregate = new Connection\Cluster\PredisCluster(new Parameters());

        $aggregate->add($connection1);
        $aggregate->add($connection2);
        $aggregate->add($connection3);

        $client = new Client($aggregate);

        /** @var Iterator */
        $iterator = $client->getIterator();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection1, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6381', $iterator->key());

        $iterator->next();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection2, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6382', $iterator->key());

        $iterator->next();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection3, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6383', $iterator->key());
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorWithNonTraversableConnectionNoException(): void
    {
        $connection = $this->getMockConnection('tcp://127.0.0.1:6381');
        $client = new Client($connection);

        /** @var Iterator */
        $iterator = $client->getIterator();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6381', $iterator->key());
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     * @skipEnterprise
     */
    public function testSetClientInfoOnConnection(): void
    {
        $client = new Client($this->getParameters());
        $libName = $client->client('LIST')[0]['lib-name'];
        $libVer = $client->client('LIST')[0]['lib-ver'];

        $this->assertEquals('predis', $libName);
        $this->assertEquals(Client::VERSION, $libVer);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClientCachesResponseOnValidCommand(): void
    {
        $client = new Client($this->getParameters(['protocol' => 3, 'cache' => true]));

        $this->assertEquals('OK', $client->set('foo', 'bar'));
        $this->assertSame('bar', $client->get('foo'));
        $this->assertSame('bar', apcu_fetch('GET_foo'));

        apcu_clear_cache();
    }

    /**
     * @group connected
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClusterClientCachesResponseOnValidCommand(): void
    {
        $client = new Client($this->getDefaultParametersArray(), ['cluster' => 'redis', 'cache' => true, 'parameters' => ['protocol' => 3]]);

        $this->assertEquals('OK', $client->set('foo', 'bar'));
        $this->assertSame('bar', $client->get('foo'));
        $this->assertSame('bar', apcu_fetch('GET_foo'));

        apcu_clear_cache();
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClientInvalidateCacheOnInvalidateResponse(): void
    {
        $client = new Client($this->getParameters(['protocol' => 3, 'cache' => true]));

        $this->assertEquals('OK', $client->set('foo', 'bar'));
        $this->assertSame('bar', $client->get('foo'));
        $this->assertSame('bar', apcu_fetch('GET_foo'));

        $this->assertEquals('OK', $client->set('foo', 'baz'));
        $this->assertNull($client->get('baz'));
        $this->assertFalse(apcu_exists('GET_foo'));

        $this->assertSame('baz', $client->get('foo'));
        $this->assertSame('baz', apcu_fetch('GET_foo'));

        apcu_clear_cache();
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClientInvalidateCacheOnInvalidateResponseWithRedisUrlGiven(): void
    {
        $url = 'redis://' . constant('REDIS_SERVER_HOST') .
            ':' . constant('REDIS_SERVER_PORT') . '?database=' . constant('REDIS_SERVER_DBNUM') .
            '&cache=true&protocol=3';

        $client = new Client($url);

        $this->assertEquals('OK', $client->set('foo', 'bar'));
        $this->assertSame('bar', $client->get('foo'));
        $this->assertSame('bar', apcu_fetch('GET_foo'));

        $this->assertEquals('OK', $client->set('foo', 'baz'));
        $this->assertNull($client->get('baz'));
        $this->assertFalse(apcu_exists('GET_foo'));

        $this->assertSame('baz', $client->get('foo'));
        $this->assertSame('baz', apcu_fetch('GET_foo'));

        apcu_clear_cache();
    }

    /**
     * @group connected
     * @group cluster
     * @group relay-incompatible
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClusterClientInvalidateCacheOnInvalidateResponse(): void
    {
        $client = new Client($this->getDefaultParametersArray(), ['cluster' => 'redis', 'cache' => true, 'parameters' => ['protocol' => 3]]);

        $this->assertEquals('OK', $client->set('foo', 'bar'));
        $this->assertSame('bar', $client->get('foo'));
        $this->assertSame('bar', apcu_fetch('GET_foo'));

        $this->assertEquals('OK', $client->set('foo', 'baz'));
        $this->assertNull($client->get('non_existing_key'));
        $this->assertFalse(apcu_exists('GET_foo'));

        $this->assertSame('baz', $client->get('foo'));
        $this->assertSame('baz', apcu_fetch('GET_foo'));

        apcu_clear_cache();
    }

    /**
     * @dataProvider commandsProvider
     * @group connected
     * @group relay-incompatible
     * @param  CommandInterface $writeCommand
     * @param  array            $writeCommandArguments
     * @param  CommandInterface $readCommand
     * @param  array            $readCommandArguments
     * @param  CommandInterface $overrideCommand
     * @param  array            $overrideCommandArguments
     * @return void
     * @requiresRedisVersion >= 7.2.0
     */
    public function testCommandResponseCachedAndInvalidateOnEnabledCache(
        CommandInterface $writeCommand,
        array $writeCommandArguments,
        CommandInterface $readCommand,
        array $readCommandArguments,
        CommandInterface $overrideCommand,
        array $overrideCommandArguments
    ): void {
        $this->assertSame(CommandInterface::READ_MODE, $readCommand->getCommandMode());
        $this->assertSame(CommandInterface::WRITE_MODE, $writeCommand->getCommandMode());
        $this->assertSame(CommandInterface::WRITE_MODE, $overrideCommand->getCommandMode());

        $readCommand->setArguments($readCommandArguments);
        $writeCommand->setArguments($writeCommandArguments);
        $overrideCommand->setArguments($overrideCommandArguments);

        // 1. Setup client in cache mode (required).
        $client = new Client($this->getParameters(['protocol' => 3, 'cache' => true]));
        $cacheKey = $readCommand->getId() . '_' . implode('_', $readCommand->getKeys());

        // 2. Flush database.
        $client->flushdb();

        // 3. Executes write command, make sure that cache flushed as well.
        $client->executeCommand($writeCommand);
        $this->assertEquals(0, apcu_cache_info()['num_entries']);

        // 4. Executes read command and cache response. Ensure that response exists in cache.
        $firstExpectedResponse = $client->executeCommand($readCommand);
        $this->assertSame($firstExpectedResponse, apcu_fetch($cacheKey));

        // 5. Executes override command and send any other read command to get invalidation from server.
        $client->executeCommand($overrideCommand);
        $this->assertNull($client->get('non_existing_key'));

        // 6. Retry read command and make sure that new value cached.
        // Also check that previous response is different from new one
        // to make sure that reads perform against server when it's required.
        $secondExpectedResponse = $client->executeCommand($readCommand);
        $this->assertSame($secondExpectedResponse, apcu_fetch($cacheKey));
        $this->assertNotSame($secondExpectedResponse, $firstExpectedResponse);
    }

    /**
     * @dataProvider moduleCommandsProvider
     * @group connected
     * @group relay-incompatible
     * @group realm-stack
     * @param  CommandInterface $writeCommand
     * @param  array            $writeCommandArguments
     * @param  CommandInterface $readCommand
     * @param  array            $readCommandArguments
     * @param  CommandInterface $overrideCommand
     * @param  array            $overrideCommandArguments
     * @return void
     */
    public function testModuleCommandResponseCachedAndInvalidateOnEnabledCache(
        CommandInterface $writeCommand,
        array $writeCommandArguments,
        CommandInterface $readCommand,
        array $readCommandArguments,
        CommandInterface $overrideCommand,
        array $overrideCommandArguments
    ): void {
        $this->assertSame(CommandInterface::READ_MODE, $readCommand->getCommandMode());
        $this->assertSame(CommandInterface::WRITE_MODE, $writeCommand->getCommandMode());
        $this->assertSame(CommandInterface::WRITE_MODE, $overrideCommand->getCommandMode());

        $readCommand->setArguments($readCommandArguments);
        $writeCommand->setArguments($writeCommandArguments);
        $overrideCommand->setArguments($overrideCommandArguments);

        // 1. Setup client in cache mode (required).
        $client = new Client($this->getParameters(['protocol' => 3, 'cache' => true]));
        $cacheKey = $readCommand->getId() . '_' . implode('_', $readCommand->getKeys());

        // 2. Flush database.
        $client->flushdb();

        // 3. Executes write command, make sure that cache flushed as well.
        $client->executeCommand($writeCommand);
        $this->assertEquals(0, apcu_cache_info()['num_entries']);

        // 4. Executes read command and cache response. Ensure that response exists in cache.
        $firstExpectedResponse = $client->executeCommand($readCommand);
        $this->assertSame($firstExpectedResponse, apcu_fetch($cacheKey));

        // 5. Executes override command and send any other read command to get invalidation from server.
        $client->executeCommand($overrideCommand);
        $this->assertNull($client->get('non_existing_key'));

        // 6. Retry read command and make sure that new value cached.
        // Also check that previous response is different from new one
        // to make sure that reads perform against server when it's required.
        $secondExpectedResponse = $client->executeCommand($readCommand);
        $this->assertSame($secondExpectedResponse, apcu_fetch($cacheKey));
        $this->assertNotSame($secondExpectedResponse, $firstExpectedResponse);
    }

    /**
     * @dataProvider clusterCommandsProvider
     * @group connected
     * @group cluster
     * @group relay-incompatible
     * @param  CommandInterface $writeCommand
     * @param  array            $writeCommandArguments
     * @param  CommandInterface $readCommand
     * @param  array            $readCommandArguments
     * @param  CommandInterface $overrideCommand
     * @param  array            $overrideCommandArguments
     * @return void
     * @requiresRedisVersion >= 7.2.0
     */
    public function testClusterCommandResponseCachedAndInvalidateOnEnabledCache(
        CommandInterface $writeCommand,
        array $writeCommandArguments,
        CommandInterface $readCommand,
        array $readCommandArguments,
        CommandInterface $overrideCommand,
        array $overrideCommandArguments
    ): void {
        $this->assertSame(CommandInterface::READ_MODE, $readCommand->getCommandMode());
        $this->assertSame(CommandInterface::WRITE_MODE, $writeCommand->getCommandMode());
        $this->assertSame(CommandInterface::WRITE_MODE, $overrideCommand->getCommandMode());

        $readCommand->setArguments($readCommandArguments);
        $writeCommand->setArguments($writeCommandArguments);
        $overrideCommand->setArguments($overrideCommandArguments);

        // 1. Setup client in cache mode (required).
        $client = new Client($this->getDefaultParametersArray(), ['cluster' => 'redis', 'cache' => true, 'parameters' => ['protocol' => 3]]);
        $cacheKey = $readCommand->getId() . '_' . implode('_', $readCommand->getKeys());

        // 2. Flush database.
        $client->flushdb();

        // 3. Executes write command, make sure that cache flushed as well.
        $client->executeCommand($writeCommand);
        $this->assertEquals(0, apcu_cache_info()['num_entries']);

        // 4. Executes read command and cache response. Ensure that response exists in cache.
        $firstExpectedResponse = $client->executeCommand($readCommand);
        $this->assertSame($firstExpectedResponse, apcu_fetch($cacheKey));

        // 5. Executes override command and send any other read command to get invalidation from server.
        $client->executeCommand($overrideCommand);
        $this->assertNull($client->get('non_existing_key'));

        // 6. Retry read command and make sure that new value cached.
        // Also check that previous response is different from new one
        // to make sure that reads perform against server when it's required.
        $secondExpectedResponse = $client->executeCommand($readCommand);
        $this->assertSame($secondExpectedResponse, apcu_fetch($cacheKey));
        $this->assertNotSame($secondExpectedResponse, $firstExpectedResponse);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns an URI string representation of the specified connection parameters.
     *
     * @param array $parameters Array of connection parameters.
     *
     * @return string URI string.
     */
    protected function getParametersString(array $parameters): string
    {
        $defaults = $this->getDefaultParametersArray();

        $scheme = $parameters['scheme'] ?? $defaults['scheme'];
        $host = $parameters['host'] ?? $defaults['host'];
        $port = $parameters['port'] ?? $defaults['port'];

        unset($parameters['scheme'], $parameters['host'], $parameters['port']);
        $uriString = "$scheme://$host:$port/?";

        foreach ($parameters as $k => $v) {
            $uriString .= "$k=$v&";
        }

        return $uriString;
    }

    /**
     * Returns a mock object simulating an aggregate connection initializer.
     *
     * @param ParametersInterface|array|string $parameters Expected connection parameters
     *
     * @return callable|MockObject
     */
    protected function getAggregateInitializer($parameters)
    {
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $parameters,
                $this->isInstanceOf('Predis\Configuration\OptionsInterface'),
                $this->isInstanceOf('Predis\Configuration\OptionInterface')
            )
            ->willReturn($connection);

        return $callable;
    }

    public function commandsProvider(): array
    {
        return [
            'BITCOUNT' => [
                new SET(),
                ['key', 'value'],
                new BITCOUNT(),
                ['key'],
                new SET(),
                ['key', 'value_new'],
            ],
            'MGET' => [
                new MSET(),
                ['foo', 'value', 'bar', 'value'],
                new MGET(),
                ['foo', 'bar'],
                new SET(),
                ['bar', 'value_new'],
            ],
            'GEODIST' => [
                new GEOADD(),
                ['key', 10.12345, 11.12345, 'foo', 12.12345, 13.12345, 'bar'],
                new GEODIST(),
                ['key', 'foo', 'bar'],
                new GEOADD(),
                ['key', 12.98765, 13.98765, 'bar'],
            ],
            'HGET' => [
                new HSET(),
                ['key', 'foo', 'bar'],
                new HGET(),
                ['key', 'foo'],
                new HSET(),
                ['key', 'foo', 'baz'],
            ],
            'LINDEX' => [
                new LPUSH(),
                ['key', 'foo'],
                new LINDEX(),
                ['key', 0],
                new LSET(),
                ['key', 0, 'bar'],
            ],
            'SMEMBERS' => [
                new SADD(),
                ['key', 'member1'],
                new SMEMBERS(),
                ['key'],
                new SADD(),
                ['key', 'member2'],
            ],
            'XLEN' => [
                new XADD(),
                ['key', ['foo' => 'bar']],
                new XLEN(),
                ['key'],
                new XADD(),
                ['key', ['bar' => 'foo']],
            ],
            'ZCARD' => [
                new ZADD(),
                ['key', 10, 'member1'],
                new ZCARD(),
                ['key'],
                new ZADD(),
                ['key', 20, 'member2'],
            ],
        ];
    }

    public function clusterCommandsProvider(): array
    {
        return [
            'BITCOUNT' => [
                new SET(),
                ['key', 'value'],
                new BITCOUNT(),
                ['key'],
                new SET(),
                ['key', 'value_new'],
            ],
            'GEODIST' => [
                new GEOADD(),
                ['key', 10.12345, 11.12345, 'foo', 12.12345, 13.12345, 'bar'],
                new GEODIST(),
                ['key', 'foo', 'bar'],
                new GEOADD(),
                ['key', 12.98765, 13.98765, 'bar'],
            ],
            'HGET' => [
                new HSET(),
                ['key', 'foo', 'bar'],
                new HGET(),
                ['key', 'foo'],
                new HSET(),
                ['key', 'foo', 'baz'],
            ],
            'LINDEX' => [
                new LPUSH(),
                ['key', 'foo'],
                new LINDEX(),
                ['key', 0],
                new LSET(),
                ['key', 0, 'bar'],
            ],
            'SMEMBERS' => [
                new SADD(),
                ['key', 'member1'],
                new SMEMBERS(),
                ['key'],
                new SADD(),
                ['key', 'member2'],
            ],
            'ZCARD' => [
                new ZADD(),
                ['key', 10, 'member1'],
                new ZCARD(),
                ['key'],
                new ZADD(),
                ['key', 20, 'member2'],
            ],
        ];
    }

    public function moduleCommandsProvider(): array
    {
        return [
            'JSON.GET' => [
                new JSONSET(),
                ['key', '$', '{"key":"value"}'],
                new JSONGET(),
                ['key'],
                new JSONSET(),
                ['key', '$', '{"key1":"value1"}'],
            ],
            'TS.GET' => [
                new TSADD(),
                ['key', 111111111, 20],
                new TSGET(),
                ['key'],
                new TSADD(),
                ['key', 222222222, 21],
            ],
        ];
    }
}
