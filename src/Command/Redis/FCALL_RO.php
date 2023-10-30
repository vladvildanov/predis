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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\CommandInterface;

/**
 * @see https://redis.io/commands/fcall_ro/
 *
 * This is a read-only variant of the FCALL command that cannot execute commands that modify data.
 */
class FCALL_RO extends RedisCommand
{
    public function getId()
    {
        return 'FCALL_RO';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = array_merge([$arguments[0], count($arguments[1])], $arguments[1]);

        if (count($arguments) > 2) {
            for ($i = 2, $iMax = count($arguments); $i < $iMax; $i++) {
                $processedArguments[] = $arguments[$i];
            }
        }

        parent::setArguments($processedArguments);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(): array
    {
        $numkeys = $this->getArgument(1);

        return $this->getArgumentsWithOffset(2, $numkeys);
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandMode(): string
    {
        return CommandInterface::READ_MODE;
    }
}
