<?php
namespace SlimTest\Test;

use PHPUnit\Framework\TestCase;
use Slim\Http\Body;
use Slim\Http\Response;
use SlimTest\ExtraResponse;

class ExtraResponseTest extends TestCase
{

    public function testGetRawBody()
    {
        $content = 'test';
        $body = new Body(tmpfile());
        $body->write($content);
        $response = new ExtraResponse(new Response(200, null, $body));
        $this->assertEquals($content, $response->getRawBody());
    }

    public function testGetParsedBody()
    {
        $body = new Body(tmpfile());
        $body->write('{"test":123}');
        $response = new ExtraResponse(
            (new Response(200, null, $body))->withHeader('Content-Type', 'application/json')
        );
        $parsedBody = $response->getParsedBody();
        $this->assertEquals(123, $parsedBody['test']);
    }

    public function testGetStatusCode()
    {
        $status = 201;
        $response = new ExtraResponse(new Response($status));
        $this->assertEquals($status, $response->getStatusCode());
    }

    public function testWithStatus()
    {
        $status = 201;
        $reasonPhrase = 'Test Created';
        $response = (new ExtraResponse(new Response()))->withStatus($status, $reasonPhrase);
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($reasonPhrase, $response->getReasonPhrase());
    }

    public function testGetReasonePhrase()
    {
        $response = new ExtraResponse(new Response(201));
        $this->assertEquals('Created', $response->getReasonPhrase());
    }

    public function testGetProtocolVersion()
    {
        $protocolVersion = '2.0';
        $response = new ExtraResponse((new Response())->withProtocolVersion($protocolVersion));
        $this->assertEquals($protocolVersion, $response->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $protocolVersion = '2.0';
        $response = (new ExtraResponse(new Response()))->withProtocolVersion($protocolVersion);
        $this->assertEquals($protocolVersion, $response->getProtocolVersion());
    }

    public function testGetHeaders()
    {
        $response = new ExtraResponse((new Response())->withHeader('Custom-Type', 'test'));
        $this->assertEquals(['Custom-Type' => ['test']], $response->getHeaders());
    }

    public function testHasHeader()
    {
        $response = new ExtraResponse((new Response())->withHeader('Custom-Type-1', 'test'));
        $this->assertEquals(true, $response->hasHeader('Custom-Type-1'));
        $this->assertEquals(false, $response->hasHeader('Custom-Type-2'));
    }

    public function testGetHeader()
    {
        $response = new ExtraResponse((new Response())->withHeader('Custom-Type-1', 'test'));
        $this->assertEquals(['test'], $response->getHeader('Custom-Type-1'));
    }

    public function testGetHeaderLine()
    {
        $response = new ExtraResponse((new Response())->withHeader('Custom-Type-1', 'test'));
        $this->assertEquals('test', $response->getHeaderLine('Custom-Type-1'));
    }

    public function testWithHeader()
    {
        $response = (new ExtraResponse(new Response()))->withHeader('Custom-Type-1', 'test');
        $this->assertEquals(['test'], $response->getHeader('Custom-Type-1'));
    }

    public function testWithAddedHeader()
    {
        $response = (new ExtraResponse((new Response())->withHeader('Custom-Type-1', 'test1')))
            ->withAddedHeader('Custom-Type-1', 'test2');
        $this->assertEquals(['test1', 'test2'], $response->getHeader('Custom-Type-1'));
    }

    public function testWithoutHeader()
    {
        $response = (new ExtraResponse((new Response())->withHeader('Custom-Type-1', 'test1')))
            ->withoutHeader('Custom-Type-1');
        $this->assertEquals(false, $response->hasHeader('Custom-Type-1'));
    }

    public function testGetBody()
    {
        $body = new Body(tmpfile());
        $response = new ExtraResponse(new Response(200, null, $body));
        $this->assertEquals($body, $response->getBody());
    }

    public function testWithBody()
    {
        $body = new Body(tmpfile());
        $response = (new ExtraResponse(new Response()))->withBody($body);
        $this->assertEquals($body, $response->getBody());
    }

}
