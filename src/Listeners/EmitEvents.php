<?php

namespace Rubix\Server\Listeners;

use Rubix\Server\Services\SSEChannel;
use Rubix\Server\Events\RequestReceived;
use Rubix\Server\Events\ResponseSent;
use Rubix\Server\Events\DatasetInferred;
use Rubix\Server\Events\MemoryUsageUpdated;

class EmitEvents implements Listener
{
    /**
     * The model channel.
     *
     * @var \Rubix\Server\Services\SSEChannel
     */
    protected \Rubix\Server\Services\SSEChannel $modelChannel;

    /**
     * The server channel.
     *
     * @var \Rubix\Server\Services\SSEChannel
     */
    protected \Rubix\Server\Services\SSEChannel $serverChannel;

    /**
     * @param \Rubix\Server\Services\SSEChannel $modelChannel
     * @param \Rubix\Server\Services\SSEChannel $serverChannel
     */
    public function __construct(SSEChannel $modelChannel, SSEChannel $serverChannel)
    {
        $this->modelChannel = $modelChannel;
        $this->serverChannel = $serverChannel;
    }

    /**
     * Return the events that this listener subscribes to.
     *
     * @return array[]
     */
    public function events() : array
    {
        return [
            DatasetInferred::class => [
                [$this, 'onDatasetInferred'],
            ],
            RequestReceived::class => [
                [$this, 'onRequestReceived'],
            ],
            ResponseSent::class => [
                [$this, 'onResponseSent'],
            ],
            MemoryUsageUpdated::class => [
                [$this, 'onMemoryUsageUpdated'],
            ],
        ];
    }

    /**
     * @param \Rubix\Server\Events\DatasetInferred $event
     */
    public function onDatasetInferred(DatasetInferred $event) : void
    {
        $this->modelChannel->emit('dataset-inferred', [
            'numSamples' => $event->dataset()->numSamples(),
        ]);
    }

    /**
     * @param \Rubix\Server\Events\RequestReceived $event
     */
    public function onRequestReceived(RequestReceived $event) : void
    {
        $request = $event->request();

        if ($request->hasHeader('Content-Length')) {
            $size = (int) $request->getHeaderLine('Content-Length');
        } else {
            $size = null;
        }

        $this->serverChannel->emit('request-received', [
            'size' => $size,
        ]);
    }

    /**
     * @param \Rubix\Server\Events\ResponseSent $event
     */
    public function onResponseSent(ResponseSent $event) : void
    {
        $response = $event->response();

        $this->serverChannel->emit('response-sent', [
            'code' => $response->getStatusCode(),
            'size' => $response->getBody()->getSize(),
        ]);
    }

    /**
     * @param \Rubix\Server\Events\MemoryUsageUpdated $event
     */
    public function onMemoryUsageUpdated(MemoryUsageUpdated $event) : void
    {
        $memory = $event->memory();

        $this->serverChannel->emit('memory-usage-updated', [
            'current' => $memory->current(),
            'peak' => $memory->peak(),
        ]);
    }
}
