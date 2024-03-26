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

namespace Predis\Connection;

use InvalidArgumentException;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\CommunicationException;
use Predis\Connection\Traits\Retry;
use Predis\Consumer\Push\PushNotificationException;
use Predis\Consumer\Push\PushResponse;
use Predis\Protocol\Parser\Strategy\Resp2Strategy;
use Predis\Protocol\Parser\Strategy\Resp3Strategy;
use Predis\Protocol\Parser\UnexpectedTypeException;
use Predis\Response\Error;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Throwable;

/**
 * Standard connection to Redis servers implemented on top of PHP's streams.
 * The connection parameters supported by this class are:.
 *
 *  - scheme: it can be either 'redis', 'tcp', 'rediss', 'tls' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection (default is 5 seconds).
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - tcp_nodelay: enables or disables Nagle's algorithm for coalescing.
 *  - persistent: the connection is left intact after a GC collection.
 *  - ssl: context options array (see http://php.net/manual/en/context.ssl.php)
 */
class StreamConnection extends AbstractConnection
{
    use Retry;

    /**
     * Disconnects from the server and destroys the underlying resource when the
     * garbage collector kicks in only if the connection has not been marked as
     * persistent.
     */
    public function __destruct()
    {
        if (isset($this->parameters->persistent) && $this->parameters->persistent) {
            return;
        }

        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertParameters(ParametersInterface $parameters)
    {
        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
            case 'unix':
            case 'tls':
            case 'rediss':
                break;

            default:
                throw new InvalidArgumentException("Invalid scheme: '$parameters->scheme'.");
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        switch ($this->parameters->scheme) {
            case 'tcp':
            case 'redis':
                return $this->tcpStreamInitializer($this->parameters);

            case 'unix':
                return $this->unixStreamInitializer($this->parameters);

            case 'tls':
            case 'rediss':
                return $this->tlsStreamInitializer($this->parameters);

            default:
                throw new InvalidArgumentException("Invalid scheme: '{$this->parameters->scheme}'.");
        }
    }

    /**
     * Creates a connected stream socket resource.
     *
     * @param ParametersInterface $parameters Connection parameters.
     * @param string              $address    Address for stream_socket_client().
     * @param int                 $flags      Flags for stream_socket_client().
     *
     * @return resource
     */
    protected function createStreamSocket(ParametersInterface $parameters, $address, $flags)
    {
        $timeout = (isset($parameters->timeout) ? (float) $parameters->timeout : 5.0);
        $context = stream_context_create(['socket' => ['tcp_nodelay' => (bool) $parameters->tcp_nodelay]]);

        if (!$resource = @stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context)) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds = floor($rwtimeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);
        }

        return $resource;
    }

    /**
     * Initializes a TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function tcpStreamInitializer(ParametersInterface $parameters)
    {
        if (!filter_var($parameters->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $address = "tcp://$parameters->host:$parameters->port";
        } else {
            $address = "tcp://[$parameters->host]:$parameters->port";
        }

        $flags = STREAM_CLIENT_CONNECT;

        if (isset($parameters->async_connect) && $parameters->async_connect) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }

        if (isset($parameters->persistent)) {
            if (false !== $persistent = filter_var($parameters->persistent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $flags |= STREAM_CLIENT_PERSISTENT;

                if ($persistent === null) {
                    $address = "{$address}/{$parameters->persistent}";
                }
            }
        }

        return $this->createStreamSocket($parameters, $address, $flags);
    }

    /**
     * Initializes a UNIX stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function unixStreamInitializer(ParametersInterface $parameters)
    {
        if (!isset($parameters->path)) {
            throw new InvalidArgumentException('Missing UNIX domain socket path.');
        }

        $flags = STREAM_CLIENT_CONNECT;

        if (isset($parameters->persistent)) {
            if (false !== $persistent = filter_var($parameters->persistent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $flags |= STREAM_CLIENT_PERSISTENT;

                if ($persistent === null) {
                    throw new InvalidArgumentException(
                        'Persistent connection IDs are not supported when using UNIX domain sockets.'
                    );
                }
            }
        }

        return $this->createStreamSocket($parameters, "unix://{$parameters->path}", $flags);
    }

    /**
     * Initializes a SSL-encrypted TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function tlsStreamInitializer(ParametersInterface $parameters)
    {
        $resource = $this->tcpStreamInitializer($parameters);
        $metadata = stream_get_meta_data($resource);

        // Detect if crypto mode is already enabled for this stream (PHP >= 7.0.0).
        if (isset($metadata['crypto'])) {
            return $resource;
        }

        if (isset($parameters->ssl) && is_array($parameters->ssl)) {
            $options = $parameters->ssl;
        } else {
            $options = [];
        }

        if (!isset($options['crypto_type'])) {
            $options['crypto_type'] = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        }

        if (!stream_context_set_option($resource, ['ssl' => $options])) {
            $this->onConnectionError('Error while setting SSL context options');
        }

        if (!stream_socket_enable_crypto($resource, true, $options['crypto_type'])) {
            $this->onConnectionError('Error while switching to encrypted communication');
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect() && $this->initCommands) {
            foreach ($this->initCommands as $command) {
                $response = $this->executeCommand($command);

                $this->handleOnConnectResponse($response, $command);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $resource = $this->getResource();
            if (is_resource($resource)) {
                fclose($resource);
            }
            parent::disconnect();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $buffer): void
    {
        $socket = $this->getResource();

        while (($length = strlen($buffer)) > 0) {
            $written = is_resource($socket) ? @fwrite($socket, $buffer) : false;

            if ($length === $written) {
                return;
            }

            if ($written === false || $written === 0) {
                $this->onConnectionError('Error while writing bytes to the server.');
            }

            $buffer = substr($buffer, $written);
        }
    }

    /**
     * {@inheritdoc}
     * @throws PushNotificationException
     * @throws CommunicationException|Throwable
     */
    public function read()
    {
        try {
            return $this->readFromSocket();
        } catch (CommunicationException $exception) {
            // Retries only on communication exception.
            return $this->retryOnError(
                [$this, 'readFromSocket'],
                function (Throwable $e) {
                    if (!$e instanceof CommunicationException) {
                        throw $e;
                    }
                }
            );
        }
    }

    /**
     * @return mixed
     * @throws CommunicationException|Throwable
     * @throws PushNotificationException
     */
    protected function readFromSocket()
    {
        $socket = $this->getResource();
        $chunk = fgets($socket);

        if ($chunk === false || $chunk === '') {
            $this->onConnectionError('Error while reading line from the server.');
        }

        try {
            $parsedData = $this->parserStrategy->parseData($chunk);
        } catch (UnexpectedTypeException $e) {
            $this->onProtocolError("Unknown response prefix: '{$e->getType()}'.");

            return;
        }

        if (!is_array($parsedData)) {
            return $parsedData;
        }

        switch ($parsedData['type']) {
            case Resp3Strategy::TYPE_PUSH:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $data[$i] = $this->read();
                }

                return new PushResponse($data);
            case Resp2Strategy::TYPE_ARRAY:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $data[$i] = $this->read();
                }

                return $data;

            case Resp2Strategy::TYPE_BULK_STRING:
                $bulkData = $this->readByChunks($socket, $parsedData['value']);

                return substr($bulkData, 0, -2);

            case Resp3Strategy::TYPE_VERBATIM_STRING:
                $bulkData = $this->readByChunks($socket, $parsedData['value']);

                return substr($bulkData, $parsedData['offset'], -2);

            case Resp3Strategy::TYPE_BLOB_ERROR:
                $errorMessage = $this->readByChunks($socket, $parsedData['value']);

                return new Error(substr($errorMessage, 0, -2));

            case Resp3Strategy::TYPE_MAP:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $key = $this->read();
                    $data[$key] = $this->read();
                }

                return $data;

            case Resp3Strategy::TYPE_SET:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $element = $this->read();

                    if (!in_array($element, $data, true)) {
                        $data[] = $element;
                    }
                }

                return $data;
        }

        return $parsedData;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $buffer = $command->serializeCommand();
        $this->write($buffer);
    }

    /**
     * {@inheritDoc}
     */
    public function hasDataToRead(): bool
    {
        $resource = $this->getResource();

        if ($resource) {
            $resourceArray = [$resource];
            $write = null;
            $except = null;
            $num = stream_select($resourceArray, $write, $except, 0);

            return $num > 0;
        }

        return false;
    }

    /**
     * Reads given resource split on chunks with given size.
     *
     * @param         $resource
     * @param  int    $chunkSize
     * @return string
     */
    private function readByChunks($resource, int $chunkSize): string
    {
        $string = '';
        $bytesLeft = ($chunkSize += 2);

        do {
            $chunk = is_resource($resource) ? fread($resource, min($bytesLeft, 4096)) : false;

            if ($chunk === false || $chunk === '') {
                $this->onConnectionError('Error while reading bytes from the server.');
            }

            $string .= $chunk;
            $bytesLeft = $chunkSize - strlen($string);
        } while ($bytesLeft > 0);

        return $string;
    }

    /**
     * Handle response from on-connect command.
     *
     * @param                   $response
     * @param  CommandInterface $command
     * @return void
     */
    private function handleOnConnectResponse($response, CommandInterface $command): void
    {
        if ($response instanceof ErrorResponseInterface) {
            $this->handleError($response, $command);
        }

        if ($command->getId() === 'HELLO' && is_array($response)) {
            // Searching for the CLIENT ID in RESP2 connection tricky because no dictionaries.
            if (
                $this->getParameters()->protocol == 2
                && false !== $key = array_search('id', $response, true)
            ) {
                $this->clientId = $response[$key + 1];
            } elseif ($this->getParameters()->protocol == 3) {
                $this->clientId = $response['id'];
            }
        }
    }

    /**
     * Handle server errors.
     *
     * @param  ErrorResponseInterface $error
     * @param  CommandInterface       $failedCommand
     * @return void
     */
    private function handleError(ErrorResponseInterface $error, CommandInterface $failedCommand): void
    {
        if ($failedCommand->getId() === 'CLIENT') {
            // Do nothing on CLIENT SETINFO command failure
            return;
        }

        if ($failedCommand->getId() === 'HELLO') {
            if (in_array('AUTH', $failedCommand->getArguments(), true)) {
                $parameters = $this->getParameters();

                $auth = new RawCommand('AUTH', [$parameters->username, $parameters->password]);
                $response = $this->executeCommand($auth);
                $this->handleOnConnectResponse($response, $auth);
            }

            $setName = new RawCommand('CLIENT', ['SETNAME', 'predis']);
            $response = $this->executeCommand($setName);
            $this->handleOnConnectResponse($response, $setName);

            return;
        }

        $this->onConnectionError("Failed: {$error->getMessage()}");
    }
}
