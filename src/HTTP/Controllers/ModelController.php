<?php

namespace Rubix\Server\HTTP\Controllers;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\Server\Models\Model;
use Rubix\Server\Services\SSEChannel;
use Rubix\Server\Helpers\JSON;
use Rubix\Server\HTTP\Responses\EventStream;
use Rubix\Server\HTTP\Responses\Success;
use Rubix\Server\Exceptions\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use React\Stream\ThroughStream;
use React\Promise\Promise;
use Exception;

class ModelController extends JSONController
{
    /**
     * The model model.
     *
     * @var \Rubix\Server\Models\Model
     */
    protected \Rubix\Server\Models\Model $model;

    /**
     * The server-sent events emitter.
     *
     * @var \Rubix\Server\Services\SSEChannel
     */
    protected \Rubix\Server\Services\SSEChannel $channel;

    /**
     * @param \Rubix\Server\Models\Model $model
     * @param \Rubix\Server\Services\SSEChannel $channel
     */
    public function __construct(Model $model, SSEChannel $channel)
    {
        $this->model = $model;
        $this->channel = $channel;
    }

    /**
     * Return the routes this controller handles.
     *
     * @return array[]
     */
    public function routes() : array
    {
        $routes = [
            '/model' => [
                'GET' => [$this, 'getModel'],
            ],
            '/model/predictions' => [
                'POST' => [
                    [$this, 'decompressRequestBody'],
                    [$this, 'parseRequestBody'],
                    [$this, 'predict'],
                ],
            ],
            '/model/events' => [
                'GET' => [$this, 'connectEventStream'],
            ],
        ];

        if ($this->model->isProbabilistic()) {
            $routes['/model/probabilities'] = [
                'POST' => [
                    [$this, 'decompressRequestBody'],
                    [$this, 'parseRequestBody'],
                    [$this, 'proba'],
                ],
            ];
        }

        if ($this->model->isScoring()) {
            $routes['/model/anomaly-scores'] = [
                'POST' => [
                    [$this, 'decompressRequestBody'],
                    [$this, 'parseRequestBody'],
                    [$this, 'score'],
                ],
            ];
        }

        return $routes;
    }

    /**
     * Handle the request and return a response or a deferred response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface|\React\Promise\PromiseInterface
     */
    public function getModel(ServerRequestInterface $request)
    {
        return new Success(self::DEFAULT_HEADERS, JSON::encode([
            'data' => [
                'model' => $this->model->asArray(),
            ],
        ]));
    }

    /**
     * Handle the request and return a response or a deferred response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface|\React\Promise\PromiseInterface
     */
    public function predict(ServerRequestInterface $request)
    {
        /** @var mixed[] $input */
        $input = $request->getParsedBody();

        try {
            if (empty($input['samples'])) {
                throw new ValidationException('Samples property must not be empty.');
            }

            $dataset = new Unlabeled($input['samples']);
        } catch (Exception $exception) {
            return $this->respondWithUnprocessable($exception);
        }

        return new Promise(function ($resolve) use ($dataset) {
            $predictions = $this->model->predict($dataset);

            $response = new Success(self::DEFAULT_HEADERS, JSON::encode([
                'data' => [
                    'predictions' => $predictions,
                ],
            ]));

            $resolve($response);
        });
    }

    /**
     * Handle the request and return a response or a deferred response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface|\React\Promise\PromiseInterface
     */
    public function proba(ServerRequestInterface $request)
    {
        /** @var mixed[] $input */
        $input = $request->getParsedBody();

        try {
            if (empty($input['samples'])) {
                throw new ValidationException('Samples property must not be empty.');
            }

            $dataset = new Unlabeled($input['samples']);
        } catch (Exception $exception) {
            return $this->respondWithUnprocessable($exception);
        }

        return new Promise(function ($resolve) use ($dataset) {
            $probabilities = $this->model->proba($dataset);

            $response = new Success(self::DEFAULT_HEADERS, JSON::encode([
                'data' => [
                    'probabilities' => $probabilities,
                ],
            ]));

            $resolve($response);
        });
    }

    /**
     * Handle the request and return a response or a deferred response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface|\React\Promise\PromiseInterface
     */
    public function score(ServerRequestInterface $request)
    {
        /** @var mixed[] $input */
        $input = $request->getParsedBody();

        try {
            if (empty($input['samples'])) {
                throw new ValidationException('Samples property must not be empty.');
            }

            $dataset = new Unlabeled($input['samples']);
        } catch (Exception $exception) {
            return $this->respondWithUnprocessable($exception);
        }

        return new Promise(function ($resolve) use ($dataset) {
            $scores = $this->model->score($dataset);

            $response = new Success(self::DEFAULT_HEADERS, JSON::encode([
                'data' => [
                    'scores' => $scores,
                ],
            ]));

            $resolve($response);
        });
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
