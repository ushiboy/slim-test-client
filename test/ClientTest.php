<?php
namespace SlimTest\Test;

use PHPUnit\Framework\TestCase;
use Slim\App;
use SlimTest\Client;

class ClientTest extends TestCase
{

    public function testRequest()
    {
        $status = 201;
        $app = new App();
        $app->get('/todos', function($req, $res) use ($status) {
            return $res->withStatus($status);
        });

        $client = new Client($app);
        $response = $client->request('GET', '/todos');
        $this->assertEquals($status, $response->getStatusCode());
    }

    public function testRequestWhenPost()
    {
        $app = new App();
        $result = null;
        $app->post('/todos', function($req, $res) use (&$result) {
            $result =  $req->getParsedBody();
            return $res;
        });

        $client = new Client($app);
        $response = $client->request('POST', '/todos', ['title' => '日本語']);
        $this->assertEquals(['title' => '日本語'], $result);
    }

    public function testRequestWithQueryString()
    {
        $app = new App();
        $result = null;
        $app->get('/todos', function($req, $res) use (&$result) {
            $result = $req->getQueryParams();
            return $res;
        });

        $client = new Client($app);
        $response = $client->request('GET', '/todos?a=1&b=2');
        $this->assertEquals(['a' => '1', 'b' => '2'], $result);
    }

    public function testRequestWithCookie()
    {
        $app = new App();
        $result = null;
        $app->get('/todos', function($req, $res) use (&$result) {
            $result = $req->getCookieParams();
            return $res;
        });

        $client = new Client($app);
        $response = $client->request('GET', '/todos', null, [
            'Cookie' => 'test=1;'
        ]);
        $this->assertEquals(['test' => '1'], $result);
    }

    public function testRequestWithHeader()
    {
        $app = new App();
        $result = null;
        $app->get('/todos', function($req, $res) use (&$result) {
            $result = $req->getHeaderLine('X-My-Custom');
            return $res;
        });

        $client = new Client($app);
        $response = $client->request('GET', '/todos', null, [
            'X-My-Custom' => 'test'
        ]);
        $this->assertEquals('test', $result);
    }

    public function testRequestWithFile()
    {
        $app = new App();
        $result = null;
        $app->post('/todos', function($req, $res) use (&$result) {
            $files = $req->getUploadedFiles();
            $result = $files['uploadfile']->getStream()->getContents();
            return $res;
        });

        $content = 'testtest';
        $f = tmpfile();
        $path = stream_get_meta_data($f)['uri'];
        fwrite($f, $content);

        $client = new Client($app);
        $response = $client->request('POST', '/todos', null, [
            'Content-Type' => 'multipart/form-data'
        ], [
            'uploadfile' => [
                'name' => 'test.txt',
                'tmp_name' => $path,
                'size' => strlen($content),
                'error' => UPLOAD_ERR_OK,
                'type' => 'text/plain'
            ]
        ]);
        $this->assertEquals($content, $result);
    }

    public function testRequestWithMultiFile()
    {
        $app = new App();
        $result = null;
        $app->post('/todos', function($req, $res) use (&$result) {
            $files = $req->getUploadedFiles();
            $result = [
                $files['uploadfile'][0]->getStream()->getContents(),
                $files['uploadfile'][1]->getStream()->getContents()
            ];
            return $res;
        });

        $content1 = 'test1';
        $f1 = tmpfile();
        $path1 = stream_get_meta_data($f1)['uri'];
        fwrite($f1, $content1);

        $content2 = 'test2';
        $f2 = tmpfile();
        $path2 = stream_get_meta_data($f2)['uri'];
        fwrite($f2, $content2);

        $client = new Client($app);
        $response = $client->request('POST', '/todos', null, [
            'Content-Type' => 'multipart/form-data'
        ], [
            'uploadfile' => [
                'name' => [
                    'test1.txt',
                    'test2.txt'
                ],
                'tmp_name' => [
                    $path1,
                    $path2
                ],
                'size' => [
                    strlen($content1),
                    strlen($content2)
                ],
                'error' => [
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK
                ],
                'type' => [
                    'text/plain',
                    'text/plain'
                ]
            ]
        ]);
        $this->assertEquals([$content1, $content2], $result);
    }

    public function testRequestJson()
    {
        $app = new App();
        $result = null;
        $app->post('/todos', function($req, $res) use (&$result) {
            $result = $req->getParsedBody();
            return $res;
        });

        $client = new Client($app);
        $response = $client->requestJson('POST', '/todos', ['title'=>'にほんご']);
        $this->assertEquals(['title' => 'にほんご'], $result);
    }

    public function testRequestJsonWithHeader()
    {
        $app = new App();
        $result = null;
        $app->post('/todos', function($req, $res) use (&$result) {
            $result = [
                $req->getHeaderLine('X-My-Custom'),
                $req->getParsedBody()
            ];
            return $res;
        });

        $client = new Client($app);
        $response = $client->requestJson('POST', '/todos', ['id'=>1234], [
            'X-My-Custom' => 'test'
        ]);
        $this->assertEquals(['test', ['id' => 1234]], $result);
    }


}
