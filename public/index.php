<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use App\Controllers\GenfinController;
use App\Services\GenfinService;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set up DI container
$container = new Container();

// 🔧 Bind LoggerInterface to Monolog
$container->set(LoggerInterface::class, function () {
    $logger = new Logger('genfin');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
    return $logger;
});

// 🔧 Bind GenfinService
$container->set(GenfinService::class, function ($c) {
    return new GenfinService($c->get(LoggerInterface::class));
});

// Set container to Slim
AppFactory::setContainer($container);
$app = AppFactory::create();

// ✅ Define Routes
$app->get('/', [GenfinController::class, 'index']);
$app->post('/webhook', [GenfinController::class, 'webhook']);

$app->run();
