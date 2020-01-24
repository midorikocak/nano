<?php

declare(strict_types=1);

use midorikocak\nano\Api;

require __DIR__ . '/vendor/autoload.php';

$api = new Api();


$message = 'Welcome to Nano';

$api->get('/', function () use ($message) {
    echo json_encode(['message' => $message], JSON_THROW_ON_ERROR, 512);
    http_response_code(200);
});

$api->post('/', function () use ($message) {
    $input = (array)json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    echo json_encode($input, JSON_THROW_ON_ERROR, 512);
    http_response_code(201);
});

$api->get('/echo/{$message}', function ($message) {
    echo json_encode(['message' => $message], JSON_THROW_ON_ERROR, 512);
    http_response_code(200);
});



$authFunction = function ($username, $password) {
    return ($username == 'username' && $password == 'password');
};

$api->auth(function () use (&$api) {

    $api->get('/entries/{id}', function ($id) {
        echo json_encode(['id' => $id], JSON_THROW_ON_ERROR, 512);
        http_response_code(201);
    });


    $api->post('/entries/{id}', function ($id) {
        echo json_encode(['id' => $id], JSON_THROW_ON_ERROR, 512);
        http_response_code(201);
    });

    $api->put('/entries/{id}', function ($id) {
        echo json_encode(['id' => $id], JSON_THROW_ON_ERROR, 512);
        http_response_code(204);
    });

    $api->delete('/entries/{id}', function ($id) {
        http_response_code(204);
    });

}, $authFunction);
