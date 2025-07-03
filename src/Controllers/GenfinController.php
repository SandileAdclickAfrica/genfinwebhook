<?php

namespace App\Controllers;

use App\Services\GenfinService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class GenfinController
{
    protected GenfinService $genfinService;
    protected LoggerInterface $logger;

    public function __construct(GenfinService $genfinService, LoggerInterface $logger)
    {
        $this->genfinService = $genfinService;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $result = $this->genfinService->lead();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function webhook(Request $request, Response $response, array $args): Response
    {
        $payload = $request->getParsedBody(); // Gets POST body (JSON or form data)

        $result = $this->genfinService->logWebhookPayload( $payload );

        $this->logger->info('Received Webhook Payload and process API', $result);

        $response->getBody()->write(json_encode([
            'status' => 'received',
            'data' => $result,
            'workload' => $payload
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

}
