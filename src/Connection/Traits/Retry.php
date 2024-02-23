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

use Throwable;

trait Retry
{
    /**
     * Retries given callback on error using exponential backoff.
     *
     * @param  callable      $callback        Callback to retry
     * @param  callable|null $onCatchCallback Callback that will be executed in catch before retry.
     * @param  int           $maxRetries      Max retries count
     * @param  int           $timeout         Retry interval in milliseconds
     * @param  int           $exponent        Exponential multiplier
     * @return mixed
     * @throws Throwable
     */
    public function retryOnError(
        callable $callback,
        ?callable $onCatchCallback = null,
        int $maxRetries = 3,
        int $timeout = 1000,
        int $exponent = 2
    ) {
        try {
            return $callback();
        } catch (Throwable $e) {
            if (is_callable($onCatchCallback)) {
                $onCatchCallback($e);
            }

            if ($maxRetries > 0) {
                usleep($timeout);

                return $this->retryOnError(
                    $callback,
                    $onCatchCallback,
                    $maxRetries - 1,
                    $timeout * $exponent,
                    $exponent
                );
            }

            throw $e;
        }
    }

    /**
     * Retries given callback on false response using exponential backoff.
     *
     * @param  callable $callback   Callback to retry
     * @param  int      $maxRetries Max retries count
     * @param  int      $timeout    Retry interval in milliseconds
     * @param  int      $exponent   Exponential multiplier
     * @return mixed
     */
    public function retryOnFalse(
        callable $callback,
        int $maxRetries = 3,
        int $timeout = 1000,
        int $exponent = 2
    ) {
        if ($result = $callback()) {
            return $result;
        }

        if ($maxRetries > 0) {
            usleep($timeout);

            return $this->retryOnFalse(
                $callback,
                $maxRetries - 1,
                $timeout * $exponent,
                $exponent
            );
        }

        return false;
    }
}
