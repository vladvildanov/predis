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

require __DIR__ . '/shared.php';
require __DIR__ . '/../vendor/autoload.php';

use Predis\Client;

$client = new Client(
    [
        'tcp://127.0.0.1:26379?username=default&password=password',
        'tcp://127.0.0.1:26380?username=default&password=password',
        'tcp://127.0.0.1:26381?username=default&password=password',
    ], [
    'replication' => 'sentinel',
    'service' => 'mymaster',
    'parameters' => [
        'username' => 'default',
        'password' => 'password',
    ],
]);

$client->set('key', 'value');
var_dump($client->get('key'));
