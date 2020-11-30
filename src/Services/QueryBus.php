<?php

namespace Rubix\Server\Services;

use Rubix\Server\Queries\Query;
use Rubix\Server\Events\QueryFailed;
use Rubix\Server\Exceptions\HandlerNotFound;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;
use Exception;

use function get_class;

/**
 * Query Bus
 *
 * The command pattern is a behavioral design pattern in which a message object is used to
 * encapsulate all the information needed to perform an action. The query bus is responsible
 * for dispatching queries to their appropriate handlers.
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class QueryBus
{
    /**
     * The query/handler bindings.
     *
     * @var \Rubix\Server\Services\Bindings
     */
    protected $bindings;

    /**
     * The event bus.
     *
     * @var \Rubix\Server\Services\EventBus
     */
    protected $eventBus;

    /**
     * @param \Rubix\Server\Services\Bindings $bindings
     * @param \Rubix\Server\Services\EventBus $eventBus
     */
    public function __construct(Bindings $bindings, EventBus $eventBus)
    {
        $this->bindings = $bindings;
        $this->eventBus = $eventBus;
    }

    /**
     * Return the query/handler bindings.
     *
     * @return \Rubix\Server\Services\Bindings
     */
    public function bindings() : Bindings
    {
        return $this->bindings;
    }

    /**
     * Dispatch the query to a handler.
     *
     * @param \Rubix\Server\Queries\Query $query
     * @throws \Rubix\Server\Exceptions\HandlerNotFound
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function dispatch(Query $query) : ExtendedPromiseInterface
    {
        $class = get_class($query);

        if (empty($this->bindings[$class])) {
            throw new HandlerNotFound($query);
        }

        $handler = $this->bindings[$class];

        $promise = new Promise(function ($resolve) use ($query, $handler) {
            $resolve($handler($query));
        });

        $onError = function (Exception $exception) use ($query) : void {
            $this->eventBus->dispatch(new QueryFailed($query, $exception));

            throw $exception;
        };

        return $promise->otherwise($onError);
    }
}
