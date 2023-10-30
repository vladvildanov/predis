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

namespace Predis\Command;

/**
 * Base class for Redis commands.
 */
abstract class Command implements CommandInterface
{
    private $slot;
    private $arguments = [];

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }

    /**
     * {@inheritdoc}
     */
    public function setRawArguments(array $arguments)
    {
        $this->arguments = $arguments;
        unset($this->slot);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandMode(): string
    {
        return CommandInterface::WRITE_MODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($index)
    {
        if (isset($this->arguments[$index])) {
            return $this->arguments[$index];
        }
    }

    /**
     * @return mixed|null
     */
    protected function getFirstArgument()
    {
        return $this->getArgument(0);
    }

    /**
     * @return array
     */
    protected function getArgumentsExceptLast(): array
    {
        return array_slice($this->getArguments(), 0, -1);
    }

    /**
     * @return array
     */
    protected function getArgumentsExceptFirst(): array
    {
        return array_slice($this->getArguments(), 1);
    }

    /**
     * @param  int   $offset
     * @param  int   $length
     * @return array
     */
    protected function getArgumentsWithOffset(int $offset, int $length): array
    {
        return array_slice($this->getArguments(), $offset, $length);
    }

    /**
     * @return array
     */
    protected function getInterleavedArguments(): array
    {
        $matchingArguments = [];

        if ($arguments = $this->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length; $i += 2) {
                $matchingArguments[$i] = $arguments[$i];
            }
        }

        return $matchingArguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlot()
    {
        return $this->slot ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResp3Response($data)
    {
        return $data;
    }

    /**
     * Normalizes the arguments array passed to a Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalizeArguments(array $arguments)
    {
        if (count($arguments) === 1 && isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }

        return $arguments;
    }

    /**
     * Normalizes the arguments array passed to a variadic Redis command.
     *
     * @param array $arguments Arguments for a command.
     *
     * @return array
     */
    public static function normalizeVariadic(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            return array_merge([$arguments[0]], $arguments[1]);
        }

        return $arguments;
    }

    /**
     * Remove all false values from arguments.
     *
     * @return void
     */
    public function filterArguments(): void
    {
        $this->arguments = array_filter($this->arguments, static function ($argument) {
            return $argument !== false && $argument !== null;
        });
    }
}
