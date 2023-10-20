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

namespace Predis\Connection\Cache;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Cache\CacheWithMetadataInterface;
use Predis\Command\CommandInterface;
use Predis\Configuration\Cache\CacheConfiguration;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Replication\SentinelReplication;
use PredisTestCase;

class CacheProxyConnectionTest extends PredisTestCase
{
    /**
     * @var MockObject|ConnectionInterface|MockObject
     */
    private $mockConnection;

    /**
     * @var MockObject|CacheConfiguration|MockObject
     */
    private $mockCacheConfiguration;

    /**
     * @var MockObject|CacheWithMetadataInterface|MockObject
     */
    private $mockCacheWithMetadata;

    /**
     * @var MockObject|CommandInterface|MockObject
     */
    private $mockCommand;

    /**
     * @var CacheProxyConnection
     */
    private $proxyConnection;

    protected function setUp(): void
    {
        $this->mockConnection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $this->mockCacheConfiguration = $this->getMockBuilder(CacheConfiguration::class)->getMock();
        $this->mockCacheWithMetadata = $this->getMockBuilder(CacheWithMetadataInterface::class)->getMock();
        $this->mockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();
        $this->mockCommand
            ->method('getKeys')
            ->willReturn(['key']);

        $this->proxyConnection = new CacheProxyConnection(
            $this->mockConnection,
            $this->mockCacheConfiguration,
            $this->mockCacheWithMetadata
        );
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testConnect(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('connect')
            ->withAnyParameters();

        $this->proxyConnection->connect();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDisconnect(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('disconnect')
            ->withAnyParameters();

        $this->proxyConnection->disconnect();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testIsConnected(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(true);

        $this->assertTrue($this->proxyConnection->isConnected());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testWriteRequest(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('writeRequest')
            ->with($this->mockCommand);

        $this->proxyConnection->writeRequest($this->mockCommand);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testReadResponse(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('readResponse')
            ->with($this->mockCommand);

        $this->proxyConnection->readResponse($this->mockCommand);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testGetSentinelConnection(): void
    {
        $mockNodeConnection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();

        $mockSentinel = $this->getMockBuilder(SentinelReplication::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSentinel
            ->expects($this->once())
            ->method('getSentinelConnection')
            ->withAnyParameters()
            ->willReturn($mockNodeConnection);

        $proxyConnection = new CacheProxyConnection(
            $mockSentinel,
            $this->mockCacheConfiguration,
            $this->mockCacheWithMetadata
        );

        $this->assertSame($mockNodeConnection, $proxyConnection->getSentinelConnection());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testGetParameters(): void
    {
        $expectedParams = ['param1' => 'value', 'param2' => 'value'];

        $this->mockConnection
            ->expects($this->once())
            ->method('getParameters')
            ->withAnyParameters()
            ->willReturn($expectedParams);

        $this->assertSame($expectedParams, $this->proxyConnection->getParameters());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandExecutesBlacklistedCommandAgainstConnection(): void
    {
        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('isWhitelistedCommand')
            ->with('HSET')
            ->willReturn(false);

        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->mockCommand)
            ->willReturn('OK');

        $this->mockCommand
            ->expects($this->once())
            ->method('getId')
            ->withAnyParameters()
            ->willReturn('HSET');

        $this->mockCommand
            ->expects($this->never())
            ->method('getArgument')
            ->withAnyParameters();

        $this->mockCacheConfiguration
            ->expects($this->never())
            ->method('getTTl')
            ->withAnyParameters();

        $this->mockCacheConfiguration
            ->expects($this->never())
            ->method('isExceedsMaxCount')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('exists')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('read')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('add')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('getTotalCount')
            ->withAnyParameters();

        $this->assertSame('OK', $this->proxyConnection->executeCommand($this->mockCommand));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandExecutesCommandAgainstCacheIfKeyExists(): void
    {
        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('isWhitelistedCommand')
            ->with('GET')
            ->willReturn(true);

        $this->mockConnection
            ->expects($this->never())
            ->method('executeCommand')
            ->withAnyParameters();

        $this->mockCommand
            ->expects($this->once())
            ->method('getId')
            ->withAnyParameters()
            ->willReturn('GET');

        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('getTTl')
            ->withAnyParameters()
            ->willReturn(100);

        $this->mockCacheConfiguration
            ->expects($this->never())
            ->method('isExceedsMaxCount')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('exists')
            ->with('GET_key')
            ->willReturn(true);

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('read')
            ->with('GET_key')
            ->willReturn('value');

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('add')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('getTotalCount')
            ->withAnyParameters();

        $this->assertSame('value', $this->proxyConnection->executeCommand($this->mockCommand));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandAgainstConnectionCacheResponseIfAllowed(): void
    {
        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('isWhitelistedCommand')
            ->with('GET')
            ->willReturn(true);

        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->mockCommand)
            ->willReturn('value');

        $this->mockCommand
            ->expects($this->once())
            ->method('getId')
            ->withAnyParameters()
            ->willReturn('GET');

        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('getTTl')
            ->withAnyParameters()
            ->willReturn(100);

        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('isExceedsMaxCount')
            ->with(101)
            ->willReturn(false);

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('exists')
            ->with('GET_key')
            ->willReturn(false);

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('read')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('add')
            ->with('GET_key', 'value', 100);

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('getTotalCount')
            ->withAnyParameters()
            ->willReturn(100);

        $this->assertSame('value', $this->proxyConnection->executeCommand($this->mockCommand));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandAgainstConnectionDoNotCacheResponseIfNotAllowed(): void
    {
        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('isWhitelistedCommand')
            ->with('GET')
            ->willReturn(true);

        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->mockCommand)
            ->willReturn('value');

        $this->mockCommand
            ->expects($this->once())
            ->method('getId')
            ->withAnyParameters()
            ->willReturn('GET');

        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('getTTl')
            ->withAnyParameters()
            ->willReturn(100);

        $this->mockCacheConfiguration
            ->expects($this->once())
            ->method('isExceedsMaxCount')
            ->with(101)
            ->willReturn(true);

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('exists')
            ->with('GET_key')
            ->willReturn(false);

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('read')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->never())
            ->method('add')
            ->withAnyParameters();

        $this->mockCacheWithMetadata
            ->expects($this->once())
            ->method('getTotalCount')
            ->withAnyParameters()
            ->willReturn(100);

        $this->assertSame('value', $this->proxyConnection->executeCommand($this->mockCommand));
    }
}
