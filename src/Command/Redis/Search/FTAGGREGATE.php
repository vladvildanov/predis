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

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.aggregate/
 *
 * Run a search query on an index, and perform aggregate transformations
 * on the results, extracting statistics etc. from them
 */
class FTAGGREGATE extends RedisCommand
{
    public function getId()
    {
        return 'FT.AGGREGATE';
    }

    public function setArguments(array $arguments)
    {
        [$index, $query] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        parent::setArguments(array_merge(
            [$index, $query],
            $commandArguments
        ));
    }

    public function parseResponse($data)
    {
        if (count($data) > 1) {
            $result = [$data[0]];

            for ($i = 1, $iMax = count($data); $i < $iMax; ++$i) {
                for ($j = 0, $jMax = count($data[$i]); $j < $jMax; ++$j) {
                    if (array_key_exists($j + 1, $data[$i])) {
                        $result[$i][(string) $data[$i][$j]] = $data[$i][++$j];
                    }
                }
            }

            return $result;
        }

        return $data;
    }
}