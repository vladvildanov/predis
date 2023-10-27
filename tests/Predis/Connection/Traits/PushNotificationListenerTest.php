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

namespace Predis\Connection\Traits;

use Predis\Consumer\Push\PushResponse;
use Predis\Consumer\Push\PushResponseInterface;
use PredisTestCase;

class PushNotificationListenerTest extends PredisTestCase
{
    /**
     * @var __anonymous@515
     */
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class() {
            use PushNotificationListener;
        };
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDispatchNotification(): void
    {
        $expectedResponse = [];

        $this->testClass->onPushNotification(
            [PushResponseInterface::INVALIDATE_DATA_TYPE => function (array $payload) use (&$expectedResponse) {
                $expectedResponse[] = $payload;
            }]
        );

        $notification = new PushResponse([PushResponseInterface::INVALIDATE_DATA_TYPE, 'foo']);
        $this->testClass->dispatchNotification($notification);

        $this->assertSame($expectedResponse, [['foo']]);
    }
}
