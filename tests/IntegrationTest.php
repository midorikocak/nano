<?php

declare(strict_types=1);

namespace midorikocak\nano;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function explode;
use function usleep;

class IntegrationTest extends TestCase
{
    private Client $http;

    private $baseUri;

    private static Process $process;

    public function setUp(): void
    {
        $this->baseUri = 'localhost:8080';
        $this->http = new Client(
            [
                'base_uri' => $this->baseUri,
                'http_errors' => false,
            ],
        );
    }

    public function tearDown(): void
    {
        unset($this->http);
    }

    public static function setUpBeforeClass(): void
    {
        self::$process = new Process(explode(' ', 'php -S localhost:8080 -t .'));
        self::$process->start();

        usleep(100000); //wait for server to get going
    }

    public static function tearDownAfterClass(): void
    {
        self::$process->stop();
    }

    public function test404(): void
    {
        $response = $this->http->request('GET', '/not-found');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test200(): void
    {
        $response = $this->http->request('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWildcard(): void
    {
        $response = $this->http->request('GET', '/echo/hello');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('hello', (string)$response->getBody());
    }

    public function testGet(): void
    {
        $response = $this->http->request('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());

        $contentType = $response->getHeaders()['Content-Type'][0];
        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testPost(): void
    {
        $data = ['message' => 'Hello Server'];
        $response = $this->http->request('POST', '/entries/1', [
            'json' => $data,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Basic dXNlcm5hbWU6cGFzc3dvcmQ=',
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testUnauthorized(): void
    {
        $data = ['message' => 'Hello Server'];
        $response = $this->http->request('POST', '/entries/1', [
            'json' => $data,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Basic ljknw=',
            ]
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testDelete(): void
    {
        $response = $this->http->request('DELETE', '/entries/1', [
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Basic dXNlcm5hbWU6cGFzc3dvcmQ=',
            ],
        ]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testPut(): void
    {
        $response = $this->http->request('PUT', '/entries/1', [
            'auth' => [
                'username', 'password',
            ],
        ]);

        $this->assertEquals(204, $response->getStatusCode());
    }
}
