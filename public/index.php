<?php

// -----------------------------------------
// Import Required Classes & Interfaces
// -----------------------------------------

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use App\Controllers\GenfinController;
use App\Services\GenfinService;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

// -----------------------------------------
// Autoload Composer Dependencies
// -----------------------------------------

require __DIR__ . '/../vendor/autoload.php';

// -----------------------------------------
// Load Environment Variables
// -----------------------------------------

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// This loads variables from the .env file into $_ENV and $_SERVER

// -----------------------------------------
// Load Database Connection
// -----------------------------------------

require __DIR__ . '/../bootstrap/database.php';
// External file used to bootstrap database settings or connections

// -----------------------------------------
// Create & Configure Dependency Container
// -----------------------------------------

$container = new Container();

/**
 * Register LoggerInterface with Monolog implementation
 *
 * @return LoggerInterface
 */
$container->set(LoggerInterface::class, function () {
    $logger = new Logger('genfin');

    // Logs will be written to logs/app.log in DEBUG mode
    $logFile = __DIR__ . '/../logs/app.log';
    $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

    return $logger;
});

/**
 * Register GenfinService and inject dependencies via container
 *
 * @param Container $c
 * @return GenfinService
 */
$container->set(GenfinService::class, function ($c) {
    return new GenfinService($c->get(LoggerInterface::class));
});

// Set container for Slim AppFactory
AppFactory::setContainer($container);

// Create Slim App Instance
$app = AppFactory::create();

// -----------------------------------------
// Add Error Middleware and Custom Handler
// -----------------------------------------

$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: true,
    logErrors: true,
    logErrorDetails: true
);

/**
 * Custom Error Handler for JSON API responses
 *
 * @param ServerRequestInterface $request
 * @param Throwable $exception
 * @param bool $displayErrorDetails
 * @param bool $logErrors
 * @param bool $logErrorDetails
 * @return ResponseInterface
 */
$errorMiddleware->setDefaultErrorHandler(function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app): ResponseInterface {
    $response = $app->getResponseFactory()->createResponse();
    $payload = [];

    // Handle HTTP 405 Method Not Allowed
    if ($exception instanceof HttpMethodNotAllowedException) {
        $payload = [
            'error' => 'Method Not Allowed',
            'allowed_methods' => $exception->getAllowedMethods()
        ];
        $statusCode = 405;
    }
    // Handle HTTP 404 Not Found
    elseif ($exception instanceof HttpNotFoundException) {
        $payload = [
            'error' => 'Not Found',
            'message' => 'The requested route was not found.'
        ];
        $statusCode = 404;
    }
    // Handle all other exceptions
    else {
        $payload = [
            'error' => 'Server Error',
            'message' => $exception->getMessage()
        ];
        $statusCode = 500;
    }

    $response->getBody()->write(json_encode($payload));

    return $response
        ->withStatus($statusCode)
        ->withHeader('Content-Type', 'application/json');
});

// -----------------------------------------
// Define Application Routes
// -----------------------------------------

/**
 * Root route to confirm API is up
 *
 * Method: GET
 * URI: /
 */
$app->get('/', [GenfinController::class, 'index']);

/**
 * Webhook listener route for receiving external POST requests
 *
 * Method: POST
 * URI: /webhook
 */
$app->post('/webhook', [GenfinController::class, 'webhook']);

// -----------------------------------------
// Run the Application
// -----------------------------------------

$app->run();
