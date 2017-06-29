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

    /**
     * Generate Upload File Data
     *
     * @param string $filePath  The upload file path
     * @param int|UPLOAD_ERR_OK $error        Upload file error code
     *
     * @return array
     */
    public static function generateUploadFile($filePath, $error = UPLOAD_ERR_OK)
    {
        return [
            'name' => basename($filePath),
            'tmp_name' => $filePath,
            'size' => filesize($filePath),
            'error' => $error,
            'type' => mime_content_type($filePath)
        ];
    }

    /**
     * Generate Upload Files Data
     *
     * @param string[] $filePaths  The upload files path
     *
     * @return array
     */
    public static function generateUploadFiles(array $filePaths)
    {
        $fileInfos = [
            'name' => [],
            'tmp_name' => [],
            'size' => [],
            'error' => [],
            'type' => []
        ];
        foreach ($filePaths as $path) {
            $info = self::generateUploadFile($path);
            array_push($fileInfos['name'], $info['name']);
            array_push($fileInfos['tmp_name'], $info['tmp_name']);
            array_push($fileInfos['size'], $info['size']);
            array_push($fileInfos['error'], $info['error']);
            array_push($fileInfos['type'], $info['type']);
        }
        return $fileInfos;
    }

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Execute request
     *
     * @param string $method    HTTP Request Method
     * @param string $url       URL
     * @param array $body       Request Body
     * @param array $headers    Request Header
     * @param array $files      Upload File Data
     *
     * @return \SlimTest\ExtraResponse
     */
    public function request(
        $method,
        $url,
        $body = null,
        array $headers = [],
        array $files = []
    ) {
        $app = $this->app;
        $uri = parse_url($url);
        $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : 'application/x-www-form-urlencoded';
        $serializedBody = $this->serializeBody($contentType, $body);
        $contentLength = mb_strlen($serializedBody);
        $serverParams = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri['path'],
            'QUERY_STRING' => isset($uri['query']) ? $uri['query'] : '',
            'SERVER_NAME' => isset($uri['host']) ? $uri['host'] : 'localhost',
            'CONTENT_TYPE' => $contentType,
            'CONTENT_LENGTH' => $contentLength
        ];
        foreach ($headers as $key => $value) {
            $serverParams['HTTP_'.$key] = $value;
        }

        $environment = Environment::mock($serverParams);
        $container = $app->getContainer();
        $container['environment'] = function () use ($environment) {
            return $environment;
        };
        $request = $this->buildRequest($environment, $serializedBody, $files);
        $container['request'] = function () use ($request) {
            return $request;
        };
        $response = $container->get('response');
        return new ExtraResponse($app->process($request, $response));
    }

    /**
     * Execute JSON request
     *
     * @param string $method    HTTP Request Method
     * @param string $url       URL
     * @param array $body       Request Body
     * @param array $headers    Request Header
     *
     * @return \SlimTest\ExtraResponse
     */
    public function requestJson($method, $uri, $body = null, $headers = [])
    {
        return $this->request($method, $uri, $body, array_merge([
            'Content-Type' => 'application/json;charset=utf8'
        ], $headers));
    }

    private function serializeBody($contentType, $body)
    {
        if (is_array($body)) {
            if (preg_match("/^application\/json/", $contentType) === 1) {
                return json_encode($body, 0);
            } elseif (in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
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
        $uploadedFiles = $this->convertUploadFiles($files);
        if ($rawBody !== '') {
            $body->write($rawBody);
        }
        return new Request($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);
    }

    private function convertUploadFiles(array $files)
    {
        $uploadedFiles = [];
        foreach ($files as $name => $file) {
            if (!isset($file['error'])) {
                if (is_array($file)) {
                    $uploadedFiles[$name] = $this->convertUploadFiles($file);
                }
                continue;
            }

            $uploadedFiles[$name] = [];
            if (!is_array($file['error'])) {
                $uploadedFiles[$name] = new UploadedFile(
                    $file['tmp_name'],
                    isset($file['name']) ? $file['name'] : basename($file['tmp_name']),
                    isset($file['type']) ? $file['type'] : null,
                    isset($file['size']) ? $file['size'] : null,
                    isset($file['error']) ? $file['error'] : UPLOAD_ERR_OK,
                    true
                );
            } else {
                $subArray = [];
                foreach ($file['error'] as $fileIdx => $error) {
                    $subArray[$fileIdx]['name'] = $file['name'][$fileIdx];
                    $subArray[$fileIdx]['type'] = $file['type'][$fileIdx];
                    $subArray[$fileIdx]['tmp_name'] = $file['tmp_name'][$fileIdx];
                    $subArray[$fileIdx]['error'] = $file['error'][$fileIdx];
                    $subArray[$fileIdx]['size'] = $file['size'][$fileIdx];
                    $uploadedFiles[$name] = $this->convertUploadFiles($subArray);
                }
            }
        }
        return $uploadedFiles;
    }
}
