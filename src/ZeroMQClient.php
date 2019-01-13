<?php

namespace Rubix\Server;

use Rubix\Server\Commands\Command;
use Rubix\Server\Serializers\Serializer;
use Rubix\Server\Serializers\Native;
use InvalidArgumentException;
use RuntimeException;
use ZMQContext;
use ZMQSocket;
use ZMQ;

/**
 * ZeroMQ Client
 * 
 * Client for the ZeroMQ Server which uses lightweight background messaging for
 * fast service to service communication.
 * 
 * > **Note**: This client requires the [ZeroMQ PHP extension](https://php.net/manual/en/book.zmq.php).
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class ZeroMQClient implements Client
{
    /**
     * The ZeroMQ client.
     * 
     * @var ZMQSocket
     */
    protected $client;    

    /**
     * The serializer used to serialize/unserialize messages before
     * and after transit.
     * 
     * @var \Rubix\Server\Serializers\Serializer
     */
    protected $serializer;

    /**
     * @param  string  $host
     * @param  int  $port
     * @param  string  $protocol
     * @param  \Rubix\Server\Serializers\Serializer|null  $serializer
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return void
     */
    public function __construct(string $host = '127.0.0.1', int $port = 5555, string $protocol = 'tcp',
                                ?Serializer $serializer = null)
    {
        if (!extension_loaded('zmq')) {
            throw new RuntimeException('Zero MQ extension is not loaded,'
                . ' check PHP configuration.');
        }

        if ($port < 0) {
            throw new InvalidArgumentException('Port number must be'
                . " a positive integer, $port given.");
        }

        if (!in_array($protocol, ZeroMQServer::TRANSPORT_PROTOCOLS)) {
            throw new InvalidArgumentException("'$protocol' is an invalid"
                . ' protocol.');
        }

        if (is_null($serializer)) {
            $serializer = new Native();
        }

        $this->client = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_REQ);

        $this->client->connect("$protocol://$host:$port");

        $this->serializer = $serializer;
    }

    /**
     * Send a command to the server and return the results.
     * 
     * @param  \Rubix\Server\Commands\Command  $command
     * @return array
     */
    public function send(Command $command) : array
    {
        $data = $this->serializer->serialize($command);

        $result = $this->client->send($data)->recv();

        return json_decode($result, true);
    }
}