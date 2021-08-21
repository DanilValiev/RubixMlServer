<?php

namespace Rubix\Server\HTTP\Controllers;

use Rubix\Server\Helpers\JSON;
use Rubix\Server\Models\Server;
use Rubix\Server\Services\SSEChannel;
use Rubix\Server\HTTP\Responses\Success;
use Rubix\Server\HTTP\Responses\EventStream;
use Psr\Http\Message\ServerRequestInterface;
use React\Stream\ThroughStream;

class ServerController extends JSONController
{
    /**
     * The server model.
     *
     * @var \Rubix\Server\Models\Server
     */
    protected \Rubix\Server\Models\Server $server;

    /**
     * The server-sent events emitter.
     *
     * @var \Rubix\Server\Services\SSEChannel
     */
    protected \Rubix\Server\Services\SSEChannel $channel;

    /**
     * @param \Rubix\Server\Models\Server $server
     * @param \Rubix\Server\Services\SSEChannel $channel
     */
    public function __construct(Server $server, SSEChannel $channel)
    {
        $this->server = $server;
        $this->channel = $channel;
    }

    /**
     * Return the routes this controller handles.
     *
     * @return array[]
     */
    public function routes() : array
    {
        return [
            '/server' => [
                'GET' => [$this, 'getServer'],
            ],
            '/server/events' => [
                'GET' => [$this, 'connectEventStream'],
            ],
        ];
    }

    /**
     * Handle the request and return a response or a deferred response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface|\React\Promise\PromiseInterface
     */
    public function getServer(ServerRequestInterface $request)
    {
        return new Success(self::DEFAULT_HEADERS, JSON::encode([
            'data' => [
                'server' => $this->server->asArray(),
            ],
        ]));
    }

    /**
     * Attach the event steam to an event source request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Rubix\Server\HTTP\Responses\EventStream
     */
    public function connectEventStream(ServerRequestInterface $request) : EventStream
    {
        if ($request->hasHeader('Last-Event-ID')) {
            $lastId = (int) $request->getHeaderLine('Last-Event-ID');
        }

        $stream = new ThroughStream();

        $this->channel->attach($stream, $lastId ?? null);

        return new EventStream($stream);
    }
}
