<?php
namespace SlimTest\Test;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Http\Body;
use Slim\Http\Response;
use SlimTest\ExtraResponse;

class ExtraResponseTest extends TestCase
{

    public function testGetRawBody()
    {
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('test');
        $response = new ExtraResponse(new App([
            'settings' => [
                'responseChunkSize' => 1
            ]
        ]), new Response(200, null, $body));
        $rawBody = $response->getRawBody();
        $this->assertEquals('test', $rawBody);
    }

    public function testGetParsedBody()
    {
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write('{"test":123}');
        $original = (new Response(200, null, $body))->withHeader('Content-Type', 'application/json');
        $response = new ExtraResponse(new App(), $original);
        $data = $response->getParsedBody();
        $this->assertEquals(123, $data['test']);
    }

    public function testGetStatusCode()
    {
        $status = 201;
        $response = new ExtraResponse(new App(), new Response($status));
        $this->assertEquals($status, $response->getStatusCode());
    }

    public function testWithStatus()
    {
        $src = new ExtraResponse(new App(), new Response());
        $status = 201;
        $reasonPhrase = 'Test Created';
        $response = $src->withStatus($status, $reasonPhrase);
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($reasonPhrase, $response->getReasonPhrase());
    }

    public function testGetReasonePhrase()
    {
        $response = new ExtraResponse(new App(), new Response(201));
        $this->assertEquals('Created', $response->getReasonPhrase());
    }

    public function testGetProtocolVersion()
    {
        $protocolVersion = '2.0';
        $response = new ExtraResponse(new App(), (new Response())->withProtocolVersion($protocolVersion));
        $this->assertEquals($protocolVersion, $response->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $src = new ExtraResponse(new App(), new Response());
        $protocolVersion = '2.0';
        $response = $src->withProtocolVersion($protocolVersion);
        $this->assertEquals($protocolVersion, $response->getProtocolVersion());
    }

    public function testGetHeaders()
    {
        $original = (new Response())->withHeader('Custom-Type', 'test');
        $response = new ExtraResponse(new App(), $original);
        $this->assertEquals($original->getHeaders(), $response->getHeaders());
    }

    public function testHasHeader()
    {
        $original = (new Response())->withHeader('Custom-Type-1', 'test');
        $response = new ExtraResponse(new App(), $original);
        $this->assertEquals(true, $response->hasHeader('Custom-Type-1'));
        $this->assertEquals(false, $response->hasHeader('Custom-Type-2'));
    }

    public function testGetHeader()
    {
        $original = (new Response())->withHeader('Custom-Type-1', 'test');
        $response = new ExtraResponse(new App(), $original);
        $this->assertEquals(['test'], $response->getHeader('Custom-Type-1'));
    }

    public function testGetHeaderLine()
    {
        $original = (new Response())->withHeader('Custom-Type-1', 'test');
        $response = new ExtraResponse(new App(), $original);
        $this->assertEquals('test', $response->getHeaderLine('Custom-Type-1'));
    }

    public function testWithHeader()
    {
        $src = new ExtraResponse(new App(), new Response());
        $response = $src->withHeader('Custom-Type-1', 'test');
        $this->assertEquals(['test'], $response->getHeader('Custom-Type-1'));
    }

    public function testWithAddedHeader()
    {
        $original = (new Response())->withHeader('Custom-Type-1', 'test1');
        $src = new ExtraResponse(new App(), $original);
        $response = $src->withAddedHeader('Custom-Type-1', 'test2');
        $this->assertEquals(['test1', 'test2'], $response->getHeader('Custom-Type-1'));
    }

    public function testWithoutHeader()
    {
        $original = (new Response())->withHeader('Custom-Type-1', 'test1');
        $src = new ExtraResponse(new App(), $original);
        $response = $src->withoutHeader('Custom-Type-1');
        $this->assertEquals(false, $response->hasHeader('Custom-Type-1'));
    }

    public function testGetBody()
    {
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new ExtraResponse(new App(), new Response(200, null, $body));
        $this->assertEquals($body, $response->getBody());
    }

    public function testWithBody()
    {
        $src = new ExtraResponse(new App(), new Response());
        $body = new Body(fopen('php://temp', 'r+'));
        $response = $src->withBody($body);
        $this->assertEquals($body, $response->getBody());
    }

}
