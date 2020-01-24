<?php

declare(strict_types=1);

namespace midorikocak\nano;

use Exception;

use function array_key_exists;
use function array_map;
use function array_shift;
use function array_values;
use function base64_decode;
use function count;
use function explode;
use function header;
use function http_response_code;
use function is_callable;
use function is_string;
use function json_encode;
use function parse_url;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function strncasecmp;
use function strtolower;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;
use const PHP_URL_PATH;

class Api
{
    private array $endpoints = [];
    private array $wildcards = [];

    private string $origin = '*';
    private int $responseCode = 404;

    public function __construct(?string $origin = '*')
    {
        $this->origin = $origin;
        header("Access-Control-Allow-Origin: $this->origin");
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: OPTIONS, POST, GET, PUT, DELETE');
        header('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');
    }

    public function setPrefix(string $prefix): void
    {
        $prefixEndpoints = [];
        $prefix = trim($prefix, '/');

        foreach ($this->endpoints as $methodName => $item) {
            if (!isset($prefixEndpoints[$methodName])) {
                $prefixEndpoints[$methodName] = [];
            }

            foreach ($item as $key => $value) {
                $key = $key === '' ? $key : '/' . $key;
                $prefixEndpoints[$methodName][$prefix . $key] = $value;
            }
        }

        $this->endpoints = $prefixEndpoints;

        $prefixWildcards = array_map(
            static function ($item) use ($prefix) {
                return $prefix . '/' . $item;
            },
            $this->wildcards
        );

        $this->wildcards = $prefixWildcards;
    }

    private function isOptions(): bool
    {
        if ((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') && $this->checkOrigin()) {
            header('Access-Control-Max-Age: 1728000');
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            http_response_code(200);
            return true;
        }

        header('Access-Control-Max-Age: 3600');
        header(
            'Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'
        );
        return false;
    }

    private function checkOrigin(): bool
    {
        if ($this->origin !== '*' && !$_SERVER['HTTP_ORIGIN'] === $this->origin) {
            header('HTTP/1.1 403 Access Forbidden');
            header('Content-Type: text/plain');
            return false;
        }
        return true;
    }

    public function __destruct()
    {
        header("Access-Control-Allow-Origin: $this->origin");
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: OPTIONS, POST, GET, PUT, DELETE');
        header('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');

        $method = strtolower($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'options') {
            $this->isOptions();
        } else {
            $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

            // Ignore uri that starts .php file extension
            //$uri = preg_replace('/^(.*?\.php\/{0,1})/', '', $uri);
            if (!isset($this->endpoints[$method])) {
                $this->endpoints[$method] = [];
            }
            $compared = $this->compareAgainstWildcards($uri);
            if (!empty($compared)) {
                try {
                    if (!array_key_exists($compared['pattern'], $this->endpoints[$method])) {
                        throw new Exception('Not found');
                    }
                    $fn = $this->endpoints[$method][$compared['pattern']];
                    $this->responseCode = 200;
                    $fn(...$compared['values']);
                } catch (Exception $e) {
                    echo json_encode($e->getMessage(), JSON_THROW_ON_ERROR, 512);
                    $this->responseCode = 404;
                }
            } elseif (isset($this->endpoints[$method][$uri])) {
                try {
                    $fn = $this->endpoints[$method][$uri];
                    $this->responseCode = 200;
                    $fn();
                } catch (Exception $e) {
                    echo json_encode($e->getMessage(), JSON_THROW_ON_ERROR, 512);
                    $this->responseCode = 404;
                }
            }
        }

        if ($this->responseCode && http_response_code() === '200') {
            http_response_code($this->responseCode);
        }
    }

    private function compareAgainstWildcards($uri): array
    {
        foreach ($this->wildcards as $wildcard) {
            $compareUri = $this->compareUri($uri, $wildcard);
            if (!empty($compareUri)) {
                return $compareUri;
            }
        }
        return [];
    }

    private function compareUri($uri, $pattern): array
    {
        // does url have brackets?
        $hasBrackets = preg_match_all('/{(.+)}/', $pattern, $vars);
        if ($hasBrackets) {
            $newPattern = preg_replace('/{.+?}/m', '([^/{}]+)', $pattern);
            $passesNewPattern = preg_match('~^' . $newPattern . '$~', $uri, $values);
            array_shift($values);
            if ($passesNewPattern) {
                return [
                    'pattern' => $pattern,
                    'uri' => $uri,
                    'vars' => $vars,
                    'values' => array_values($values),
                ];
            }
        }
        return [];
    }

    private function hasBrackets($uri)
    {
        return preg_match('/{(.*?)}/', $uri);
    }

    public function getResponseCode(int $code): void
    {
        $this->responseCode = $code;
    }

    public function auth(callable $fn, callable $login)
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && strncasecmp($_SERVER['HTTP_AUTHORIZATION'], 'basic ', 6) === 0) {
            $exploded = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)), 2);

            if (count($exploded) === 2) {
                [$un, $pw] = $exploded;
            }
            if ($login($un ?? '', $pw ?? '')) {
                $fn();
            } else {
                $this->responseCode = 401;
            }
        }
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->endpoints[$name])) {
            $this->endpoints[$name] = [];
        }

        if (count($arguments) !== 2) {
            return;
        }

        if (is_string($arguments[0]) && is_callable($arguments[1])) {
            $endpoint = parse_url(trim($arguments[0], '/'), PHP_URL_PATH);
            if ($this->hasBrackets($arguments[0])) {
                $this->wildcards[] = $endpoint;
            }
            $this->endpoints[$name][$endpoint] = $arguments[1];
        }
    }
}
