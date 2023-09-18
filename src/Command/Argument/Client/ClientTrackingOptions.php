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

namespace Predis\Command\Argument\Client;

use Predis\Command\Argument\ArrayableArgument;

class ClientTrackingOptions implements ArrayableArgument
{
    private $arguments = [];

    /**
     * Send invalidation messages to the connection with the specified ID.
     *
     * @param  int   $clientId
     * @return $this
     */
    public function redirect(int $clientId): self
    {
        array_push($this->arguments, 'REDIRECT', $clientId);

        return $this;
    }

    /**
     * For broadcasting, register a given key prefix, so that notifications will be provided only for keys starting with this string.
     *
     * @param  string ...$prefix
     * @return $this
     */
    public function prefix(string ...$prefix): self
    {
        $prefixes = func_get_args();

        foreach ($prefixes as $prefixName) {
            array_push($this->arguments, 'PREFIX', $prefixName);
        }

        return $this;
    }

    /**
     * Enable tracking in broadcasting mode.
     *
     * @return $this
     */
    public function broadcast(): self
    {
        $this->arguments[] = 'BCAST';

        return $this;
    }

    /**
     * When broadcasting is NOT active, normally don't track keys in read only commands,
     * unless they are called immediately after a CLIENT CACHING yes command.
     *
     * @return $this
     */
    public function optIn(): self
    {
        $this->arguments[] = 'OPTIN';

        return $this;
    }

    /**
     * When broadcasting is NOT active, normally don't track keys in read only commands,
     * unless they are called immediately after a CLIENT CACHING yes command.
     *
     * @return $this
     */
    public function optOut(): self
    {
        $this->arguments[] = 'OPTOUT';

        return $this;
    }

    /**
     * Don't send notifications about keys modified by this connection itself.
     *
     * @return $this
     */
    public function noLoop(): self
    {
        $this->arguments[] = 'NOLOOP';

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
