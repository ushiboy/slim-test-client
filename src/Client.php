<?php
namespace SlimTest;

use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Cookies;
use Slim\Http\Body;
use Slim\Http\UploadedFile;

class Client
{

    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function request(
        string $method,
        string $url,
        $body = null,
        array $headers = [],
        array $files = []
    ): ExtraResponse {
        $app = $this->app;
        $uri = parse_url($url);
        $contentType = $headers['Content-Type'] ?? 'application/x-www-form-urlencoded';
        $serializedBody = $this->serializeBody($contentType, $body);
        $contentLength = mb_strlen($serializedBody);

        $fixedHeaders = [];
        foreach ($headers as $key => $value) {
            $fixedHeaders['HTTP_'.$key] = $value;
        }

        $environment = Environment::mock(array_merge([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri['path'],
            'QUERY_STRING' => $uri['query'] ?? '',
            'SERVER_NAME' => $uri['host'] ?? 'localhost',
            'CONTENT_TYPE' => $contentType,
            'CONTENT_LENGTH' => $contentLength
        ], $fixedHeaders));
        $container = $app->getContainer();
        $container['environment'] = function () use ($environment) {
            return $environment;
        };
        $request = $this->buildRequest($environment, $serializedBody, $files);
        $container['request'] = function () use ($request) {
            return $request;
        };
        $response = $container->get('response');
        return new ExtraResponse($app, $app->process($request, $response));
    }

    public function requestJson(string $method, string $uri, $body = null): ExtraResponse
    {
        return $this->request($method, $uri, $body, [
            'Content-Type' => 'application/json;charset=utf8'
        ]);
    }

    private function serializeBody(string $contentType, $body): string
    {
        if (is_array($body)) {
            if (preg_match("/^application\/json/", $contentType) === 1) {
                return json_encode($body, 0);
            } elseif (preg_match("/^application\/x-www-form-urlencoded/", $contentType) === 1) {
                return http_build_query($body);
            }
        } elseif (is_string($body)) {
            return $body;
        }
        return '';
    }

    private function buildRequest(Environment $environment, $rawBody, $files)
    {
        $method = $environment['REQUEST_METHOD'];
        $uri = Uri::createFromEnvironment($environment);
        $headers = Headers::createFromEnvironment($environment);
        $cookies = Cookies::parseHeader($headers->get('Cookie', []));
        $serverParams = $environment->all();
        $body = new Body(fopen('php://temp', 'w+'));
        $uploadedFiles = [];
        foreach ($files as $name => $f) {
            $uploadedFiles[$name] = new UploadedFile(
                $f['tmp_name'],
                $f['name'] ?? null,
                $f['type'] ?? null,
                $f['size'] ?? null,
                $f['error'] ?? 0,
                true
            );
        }
        if ($rawBody !== '') {
            $body->write($rawBody);
        }
        return new Request($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);
    }
}
