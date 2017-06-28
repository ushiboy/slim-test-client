<?php
namespace SlimTest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ExtraResponse implements ResponseInterface
{

    private $originalResponse;

    public function __construct(ResponseInterface $response)
    {
        $this->originalResponse = $response;
    }

    public function getRawBody()
    {
        $body = $this->getBody();
        $body->rewind();
        return $body->getContents();
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
        return new self($this->originalResponse->withStatus($code, $reasonPhrase));
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
        return new self($this->originalResponse->withProtocolVersion($version));
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
        return new self($this->originalResponse->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value)
    {
        return new self($this->originalResponse->withAddedHeader($name, $value));
    }

    public function withoutHeader($name)
    {
        return new self($this->originalResponse->withoutHeader($name));
    }

    public function getBody()
    {
        return $this->originalResponse->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        return new self($this->originalResponse->withBody($body));
    }
}
