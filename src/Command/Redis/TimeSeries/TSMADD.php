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

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ts.madd/
 *
 * Append new samples to one or more time series.
 */
class TSMADD extends RedisCommand
{
    public function getId()
    {
        return 'TS.MADD';
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(): array
    {
        $arguments = $this->getArguments();
        $keys = [];

        for ($i = 0, $iMax = count($arguments); $i <= $iMax; $i++) {
            if (array_key_exists($i, $arguments)) {
                $keys[] = $arguments[$i];
                $i += 2;
            }
        }

        return $keys;
    }
}
