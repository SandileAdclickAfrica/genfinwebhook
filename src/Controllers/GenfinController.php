<?php

namespace App\Controllers;

use App\Services\GenfinService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

/**
 * Controller responsible for handling Genfin-related webhook interactions.
 *
 * This class exposes two endpoints:
 *  - index(): Health check route.
 *  - webhook(): Endpoint to receive and process webhook payloads from Genfin.
 */
class GenfinController
{
    /**
     * @var GenfinService Handles business logic for Genfin webhook processing.
     */
    private GenfinService $genfinService;

    /**
     * @var LoggerInterface PSR-3 compliant logger for capturing application logs.
     */
    private LoggerInterface $logger;

    /**
     * GenfinController constructor.
     *
     * @param GenfinService   $genfinService Business logic service for Genfin integrations.
     * @param LoggerInterface $logger        Logger instance for logging webhook activity and errors.
     */
    public function __construct(GenfinService $genfinService, LoggerInterface $logger)
    {
        $this->genfinService = $genfinService;
        $this->logger = $logger;
    }

    /**
     * Health check endpoint to confirm the webhook system is operational.
     *
     * Example Request:
     *  GET /genfin
     *
     * Example Response:
     * {
     *   "message": "Genfin Webhook is running",
     *   "status": "ok",
     *   "timestamp": "2025-07-04T10:55:32+02:00"
     * }
     *
     * @param Request  $request  Incoming HTTP request.
     * @param Response $response HTTP response to return.
     * @param array    $args     Route parameters (not used here).
     * @return Response JSON response indicating the service status.
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $data = [
            'message'   => 'Genfin Webhook is running',
            'status'    => 'ok',
            'timestamp' => date(DATE_ATOM), // ISO 8601 format
        ];

        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Main endpoint for receiving webhook notifications from Genfin.
     *
     * This method:
     * - Extracts the webhook payload (supports both JSON and form-data).
     * - Passes the payload to the GenfinService for logging and further processing.
     * - Logs both the incoming data and the processed result.
     * - Returns a standardized JSON response indicating success.
     *
     * Example Request:
     *  POST /genfin/webhook
     *  Body: application/json or form-data
     *
     * Example Response:
     * {
     *   "status": "received",
     *   "data": {
     *     "saved": true,
     *     "record_id": 123
     *   },
     *   "payload": {
     *     "lead_id": "ABC123",
     *     "event": "application_submitted"
     *   }
     * }
     *
     * @param Request  $request  Incoming HTTP POST request containing webhook data.
     * @param Response $response HTTP response to return.
     * @param array    $args     Route parameters (not used).
     * @return Response JSON response confirming the webhook was received and processed.
     */
    public function webhook(Request $request, Response $response, array $args): Response
    {
        // Parse POST body as associative array (works for form-data or JSON if Content-Type is set properly)
        $payload = $request->getParsedBody();

        // Delegate processing to the service layer (e.g., save to DB, trigger other actions)
        $result = $this->genfinService->logWebhookPayload($payload);

        // Write detailed log for auditing or debugging
        $this->logger->info('Received webhook payload and processed API.', [
            'payload' => $payload,
            'result'  => $result,
        ]);

        // Send structured JSON response
        $response->getBody()->write(json_encode([
            'status'  => 'received',
            'data'    => $result,
            'payload' => $payload,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
