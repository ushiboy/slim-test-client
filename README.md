# Slim Test Client

Unofficial test client for Slim Framework.

(Draft)


## Usage

```php
<?php
class SampleTest extends \PHPUnit\Framework\TestCase
{
    public function testRequest()
    {
        $app = new \Slim\App();
        $app->get('/test', function($req, $res) {
            $res->getBody()->write('Hello, world!');
            return $res;
        });

        $client = new \SlimTest\Client($app);
        $response = $client->request('GET', '/test');
        $this->assertEquals('Hello, world!', $response->getRawBody());
    }
}
```
