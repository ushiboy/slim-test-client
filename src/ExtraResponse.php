<?php
namespace SlimTest;

use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ExtraResponse implements ResponseInterface
{

    private $app;

    private $originalResponse;

    public function __construct(App $app, ResponseInterface $response)
    {
        $this->app = $app;
        $this->originalResponse = $response;
    }

    public function getRawBody()
    {
        $container = $this->app->getContainer();
        $chunkSize = $container->get('settings')['responseChunkSize'] ?? 4096;
        $body = $this->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $contentLength = $this->hasHeader('Content-Length')
            ? intval($this->getHeaderLine('Content-Length')) : $body->getSize();
        $result = [];
        if (isset($contentLength)) {
            $totalChunks    = ceil($contentLength / $chunkSize);
            $lastChunkSize  = $contentLength % $chunkSize;
            $currentChunk   = 0;
            while (!$body->eof() && $currentChunk < $totalChunks) {
                if (++$currentChunk == $totalChunks && $lastChunkSize > 0) {
                    $chunkSize = $lastChunkSize;
                }
                $chunk = $body->read($chunkSize);
                array_push($result, $chunk);
            }
        }
        return implode($result);
    }

    public function getParsedBody()
    {
        $contentType = $this->getHeaderLine('Content-Type');
        if (preg_match("/^application\/json/", $contentType) === 1) {
            return json_decode($this->getRawBody(), true);
        }
        return $this->getRawBody();
    }

    public function getStatusCode()
    {
        return $this->originalResponse->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        return new self($this->app, $this->originalResponse->withStatus($code, $reasonPhrase));
    }

    public function getReasonPhrase()
    {
        return $this->originalResponse->getReasonPhrase();
    }

    public function getProtocolVersion()
    {
        return $this->originalResponse->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        return new self($this->app, $this->originalResponse->withProtocolVersion($version));
    }

    public function getHeaders()
    {
        return $this->originalResponse->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->originalResponse->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->originalResponse->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->originalResponse->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        return new self($this->app, $this->originalResponse->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value)
    {
        return new self($this->app, $this->originalResponse->withAddedHeader($name, $value));
    }

    public function withoutHeader($name)
    {
        return new self($this->app, $this->originalResponse->withoutHeader($name));
    }

    public function getBody()
    {
        return $this->originalResponse->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        return new self($this->app, $this->originalResponse->withBody($body));
    }
}
