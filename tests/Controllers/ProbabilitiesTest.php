<?php

namespace Rubix\Server\Tests\Controllers;

use Rubix\Server\Controllers\Probabilities;
use Rubix\Server\Controllers\Controller;
use Rubix\ML\Classifiers\GaussianNB;
use React\Http\Io\ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use PHPUnit\Framework\TestCase;

class ProbabilitiesTest extends TestCase
{
    protected $controller;

    public function setUp()
    {
        $estimator = $this->createMock(GaussianNB::class);

        $estimator->method('proba')->willReturn([
            [
                'positive' => 0.8,
                'negative' => 0.2,
            ],
        ]);

        $this->controller = new Probabilities($estimator);
    }

    public function test_build_controller()
    {
        $this->assertInstanceOf(Probabilities::class, $this->controller);
        $this->assertInstanceOf(Controller::class, $this->controller);
    }

    public function test_handle()
    {
        $request = new ServerRequest('POST', '/example', [], json_encode([
            'samples' => [
                ['The first step is to establish that something is possible, then probability will occur.'],
            ],
        ]) ?: null);

        $response = $this->controller->handle($request, []);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $probabilities = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals([
            'positive' => 0.8,
            'negative' => 0.2,
        ], $probabilities[0]);
    }
}