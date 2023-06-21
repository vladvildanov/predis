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

namespace Predis\Command\Strategy\ContainerCommands\XGroup;

use Predis\Command\Strategy\SubcommandStrategyInterface;

class CreateStrategy implements SubcommandStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function processArguments(array $arguments): array
    {
        $processedArguments = [$arguments[0], $arguments[1], $arguments[2], $arguments[3]];

        if (array_key_exists(4, $arguments) && true === $arguments[4]) {
            $processedArguments[] = 'MKSTREAM';
        }

        if (array_key_exists(5, $arguments)) {
            array_push($processedArguments, 'ENTRIESREAD', $arguments[5]);
        }

        return $processedArguments;
    }
}