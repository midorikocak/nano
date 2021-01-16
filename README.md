![nano API](nano.png)
# Nano

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

**CONTRIBUTORS WELCOME**

Nano is a very very tiny php library that allows you to create very fast rest APIs. 

Think it's like Slim but Nano is only ~6.4 Kilobytes.

## Requirements

Strictly requires PHP 7.4.

## Install

Via Composer

``` bash
$ composer require midorikocak/nano
```

## Usage

Simply instantiate and include in your app.

``` php
use midorikocak\nano\Api;

require __DIR__ . '/vendor/autoload.php';

$api = new Api();

```

I know. It's not static.

### Defining REST resources 

Defining rest routes and using wildcards are easy.

``` php
$message = 'Welcome to Nano';

$api->get('/', function () use ($message) {
    echo json_encode(['message' => $message]);
    http_response_code(200);
});

$api->post('/', function () use ($message) {
    $input = (array)json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    echo json_encode($input);
    http_response_code(201);
});

$api->get('/echo/{$message}', function ($message) {
    echo json_encode(['message' => $message]);
    http_response_code(200);
});

```

### Basic Auth

It's possible hide your routed behind an authentication layer. Currently it expects basic auth, more methods to come soon.

``` php

$authFunction = function ($username, $password) {
    return ($username == 'username' && $password == 'password');
};

$api->auth(function () use (&$api) {

    $api->get('/entries/{id}', function ($id) {
        echo json_encode(['id' => $id]);
        http_response_code(201);
    });

    $api->post('/entries/{id}', function ($id) {
        echo json_encode(['id' => $id]);
        http_response_code(201);
    });

    $api->put('/entries/{id}', function ($id) {
        echo json_encode(['id' => $id]);
        http_response_code(204);
    });

    $api->delete('/entries/{id}', function ($id) {
        http_response_code(204);
    });

}, $authFunction);
```

Hence the basic auth is not encrypted, using https is strictly advised.

## Testing

You can test your live API using `Guzzle/Client`

``` php
<?php

declare(strict_types=1);

namespace midorikocak\nano;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function testGet(): void
    {
        $client = new Client(
            [
                'base_uri' => $this->baseUri,
                'http_errors' => false,
            ],
        );

        $response = $client->request('GET', '/echo/hello');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('hello', (string)$response->getBody());
    }
```

## Motivation

Mostly educational purposes.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email mtkocak@gmail.com instead of using the issue tracker.

## Credits

- [Midori Kocak][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/midorikocak/nano.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/midorikocak/nano/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/midorikocak/nano.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/midorikocak/nano.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/midorikocak/nano.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/midorikocak/nano
[link-travis]: https://travis-ci.org/midorikocak/nano
[link-scrutinizer]: https://scrutinizer-ci.com/g/midorikocak/nano/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/midorikocak/nano
[link-downloads]: https://packagist.org/packages/midorikocak/nano
[link-author]: https://github.com/midorikocak
[link-contributors]: ../../contributors
