<?php declare(strict_types=1);

namespace Domnikl\Statsd\Connection;

use Domnikl\Statsd\Connection as Connection;

/**
 * encapsulates the connection to the statsd service in TCP mode
 *
 * @codeCoverageIgnore
 */
class TcpSocket extends InetSocket implements Connection
{
    const HEADER_SIZE = 20;

    /**
     * the used TCP socket resource
     *
     * @var resource|null|false
     */
    private $socket;

    /**
     * sends a message to the socket
     *
     * @param string $message
     *
     * @codeCoverageIgnore
     * this is ignored because it writes to an actual socket and is not testable
     */
    public function send(string $message)
    {
        try {
            parent::send($message);
        } catch (TcpSocketException $e) {
            throw $e;
        } catch (\Exception $e) {
            // ignore it: stats logging failure shouldn't stop the whole app
        }
    }

    /**
     * @param string $message
     */
    protected function writeToSocket($message)
    {
        fwrite($this->socket, $message);
    }

    /**
     * @param string $host
     * @param int $port
     * @param int|null $timeout
     * @param bool $persistent
     */
    protected function connect(string $host, int $port, $timeout, bool $persistent)
    {
        $errorNumber = null;
        $errorMessage = null;

        $url = 'tcp://' . $host;

        if ($persistent) {
            $socket = @pfsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        } else {
            $socket = @fsockopen($url, $port, $errorNumber, $errorMessage, $timeout);
        }

        if ($socket === false) {
            throw new TcpSocketException($host, $port, $errorMessage);
        }

        $this->socket = $socket;
    }

    protected function isConnected(): bool
    {
        return is_resource($this->socket) && !feof($this->socket);
    }

    public function close()
    {
        fclose($this->socket);

        $this->socket = null;
    }

    protected function getProtocolHeaderSize(): int
    {
        return self::HEADER_SIZE;
    }

    protected function allowFragmentation(): bool
    {
        return true;
    }
}
