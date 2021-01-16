<?php

declare(strict_types=1);

namespace midorikocak\nano;

use PHPUnit\Framework\TestCase;

use function http_response_code;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class ApiUnitTest extends TestCase
{
    private Api $api;

    public function setUp(): void
    {
        parent::setUp();
        $this->api = new Api();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->api);
    }

    /**
     * @runInSeparateProcess
     */
    public function testApi(): void
    {
        $api = $this->api;
        $message = 'Hello REST';
        $this->assertNull(
            $api->get(
                '/', function () {
                    http_response_code(200);
                }
            )
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testWildcards(): void
    {
        $api = $this->api;
        $this->assertNull(
            $api->get(
                '/echo/{$message}', function ($message) {
                    echo json_encode(['message' => $message], JSON_THROW_ON_ERROR, 512);
                    http_response_code(200);
                }
            )
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testAuth(): void
    {
        $api = $this->api;
        $auth = function ($username, $password) {
            return true;
        };
        $this->assertNull(
            $api->auth(
                function () use ($api) {
                    $api->get(
                        '/echo/{$message}', function ($message) {
                            echo json_encode(['message' => $message], JSON_THROW_ON_ERROR, 512);
                            http_response_code(200);
                        }
                    );
                },
                $auth
            )
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testUnauthorized(): void
    {
        $api = $this->api;
        $auth = function ($username, $password) {
            return false;
        };
        $this->assertNull(
            $api->auth(
                function () use ($api) {
                    $api->get(
                        '/echo/{$message}', function ($message) {
                            echo json_encode(['message' => $message], JSON_THROW_ON_ERROR, 512);
                            http_response_code(200);
                        }
                    );
                },
                $auth
            )
        );
    }
}
