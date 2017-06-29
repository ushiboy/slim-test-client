# Slim Test Client

Unofficial test client for Slim Framework.

## Usage

### Basic usage


To get started, you first instantiate and configure the Slim application and it pass to SlimTest client.
Then you run the client's `request` method and get the response object.

The response object has implements `\Psr\Http\Message\ResponseInterface`, and also it has `getRawBody` and `getParsedBody` methods as an extention.

The `getRawBody` method returns a content of the response body.
If the Content-Type of the response is `application/json`, `getParsedBody` method returns the decoded JSON data.

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

### Request Body

When passing the request body, set it as an associative array in the 3rd argument and use it.

```php
$client = new \SlimTest\Client($app);
$response = $client->request('POST', '/test', ['title' => 'hoge']);
```


### Query String

When using the query string, add it to the URL of the 2nd argument and use it.

```php
$client = new \SlimTest\Client($app);
$response = $client->request('GET', '/test?a=1&b=2');
```

### Header

If you want to add cookies or custom headers, use an associative array as the 4th argument and use it.

```php
$client = new \SlimTest\Client($app);
// add cookie
$response = $client->request('GET', '/test', null, [
    'Cookie' => 'test=1;'
]);

// add custom header
$response = $client->request('GET', '/test', null, [
    'X-My-Custom' => 'test'
]);
```

### Upload File

When uploading a file, set it as associative array in the 5th argument.
The structure of an associative array is the same as `$_FILES`.

```php
$client = new \SlimTest\Client($app);
$response = $client->request('POST', '/test', null, [
    'Content-Type' => 'multipart/form-data'
], [
    'uploadfile' => [
        'name' => 'test.txt',
        'tmp_name' => '/path/to/file',
        'size' => $yourFileSize,
        'error' => UPLOAD_ERR_OK,
        'type' => 'text/plain'
    ]
]);

// or use \SlimTest\Client::generateUploadFile method
$response = $client->request('POST', '/test', null, [
    'Content-Type' => 'multipart/form-data'
], [
    'uploadfile' => Client::generateUploadFile('/path/to/file')
]);
```

### JSON

If you use the `requestJson` method, you can omit the Content-Type.
In this case, the request body is treated as JSON data.


```php
$client = new \SlimTest\Client($app);
$response = $client->requestJson('POST', '/test', ['title'=>'hoge']);
```
