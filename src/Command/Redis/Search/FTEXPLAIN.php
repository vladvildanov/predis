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

use Predis\Command\Argument\Search\SearchArguments;
use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.explain/
 *
 * Return the execution plan for a complex query.
 */
class FTEXPLAIN extends RedisCommand
{
    public function getId()
    {
        return 'FT.EXPLAIN';
    }

    public function setArguments(array $arguments)
    {
        [$index, $query] = $arguments;
        $commandArguments = [];

        if (!empty($arguments[2])) {
            $commandArguments = (new SearchArguments())->dialect($arguments[2])->toArray();
        }

        parent::setArguments(array_merge(
            [$index, $query],
            $commandArguments
        ));
    }
}
