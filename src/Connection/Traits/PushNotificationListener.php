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

use Predis\Connection\ConnectionInterface;
use Predis\Consumer\Push\PushNotificationException;
use Predis\Consumer\Push\PushResponse;

/**
 * @mixin ConnectionInterface
 */
trait PushNotificationListener
{
    /**
     * @var callable[]
     */
    protected $callbackDictionary = [];

    /**
     * Dispatch given notification to appropriate callback.
     *
     * @param  PushResponse              $notification
     * @return void
     * @throws PushNotificationException
     */
    public function dispatchNotification(PushResponse $notification): void
    {
        $dataType = $notification->getDataType();

        if (array_key_exists($dataType, $this->callbackDictionary)) {
            $callback = $this->callbackDictionary[$dataType];
            $callback($notification->getPayload());
        }
    }

    /**
     * Callbacks that should be invoked on push notification being received.
     * Keys correspond to push data type, values to callbacks.
     *
     * @param  array $callbackDictionary
     * @return void
     */
    public function onPushNotification(array $callbackDictionary): void
    {
        $this->callbackDictionary = $callbackDictionary;
    }
}
