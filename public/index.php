<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


require __DIR__ . '/../vendor/autoload.php';





$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $env = $_ENV['S3_BUCKET']; // 'local'
    $response->getBody()->write( $env );
    return $response;
});

$app->get('/about', function ($request, $response, $args) {
    $response->getBody()->write("This is the About page.");
    return $response;
});





$app->run();