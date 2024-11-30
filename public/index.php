<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

try {

    $app = AppFactory::create();
    $app->setBasePath('/api/public');

    // Require routes
    require_once(__DIR__ . '/../routes/weeks.php');
    require_once(__DIR__ . '/../routes/users.php');

    // CORS Route
    $app->options('/{routes:.+}', function (Request $request, Response $response, $args) {
        $response->getBody()->write('{ "CORS" : "OK" }');
        return $response;
    });

    // Middlewares
    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Api-Key')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    });

    $app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function ($request, $response) {
        $response->getBody()->write("Rota não encontrada.");
        return $response;
    });

    $app->run();

} catch (Exception $err) {
    echo '<pre>';
    var_dump($err); die;
}