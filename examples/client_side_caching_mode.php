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

require __DIR__ . '/../autoload.php';

if (PHP_SAPI !== 'fpm-fcgi') {
    exit('This example available only in FPM mode.');
}

// 1. Create client with enabled cache and RESP3 connection mode.
$client = new \Predis\Client(['cache' => true, 'protocol' => 3]);
$client->flushall();

// 2. Set key into Redis storage.
$client->set('foo', 'bar');

// 3. Retrieves from Redis storage and cache response.
echo nl2br('Value in Redis: ' . $client->get('foo') . "\n");

// 4. Check that command response is cached.
echo nl2br('Value in cache: ' . apcu_fetch('GET_foo') . "\n");

// 5. Set new value for the same key.
$client->set('foo', 'baz');

// 6. Send any other read command, so invalidation message will be received.
$client->get('baz');

// 7. Retrieves updated value from Redis storage again.
echo nl2br('New value in Redis: ' . $client->get('foo') . "\n");
echo nl2br('New value in cache: ' . apcu_fetch('GET_foo') . "\n");
