<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\API;

require_once __DIR__ . '/../Test.php';
require_once __DIR__ . '/Endpoint.php';

use MailPoet\API\REST\API;
use MailPoet\API\REST\EndpointContainer;
use MailPoet\API\REST\Request;
use MailPoet\REST\Automation\API\Endpoints\Endpoint;
use MailPoet\REST\Test;
use MailPoet\WP\Functions as WPFunctions;

class EndpointTest extends Test {
  /** @var string */
  private $prefix = '/mailpoet/v1/mailpoet-api-testing-route';

  public function testGetParams(): void {
    $path = strtolower(__FUNCTION__);
    $request = null;
    $this->registerTestingGetRoute($path, function (Request $req) use (&$request) {
      $request = $req;
    });

    $response = $this->get("$this->prefix/$path", ['query' => [
      'required' => 'required',
      'string' => 'abc',
      'number-1' => '0.123',
      'number-2' => '123',
      'integer-1' => '123',
      'integer-2' => '-123',
      'boolean-1' => '0',
      'boolean-2' => 'true',
    ]]);

    $this->assertInstanceOf(Request::class, $request);
    $this->assertSame(['data' => null], $response);
    $this->assertSame([
      'required' => 'required',
      'string' => 'abc',
      'number-1' => 0.123,
      'number-2' => 123.0,
      'integer-1' => 123,
      'integer-2' => -123,
      'boolean-1' => false,
      'boolean-2' => true,
    ], $request->getParams());
  }

  public function testPostParams(): void {
    $path = strtolower(__FUNCTION__);
    $request = null;
    $this->registerTestingPostRoute($path, function (Request $req) use (&$request) {
      $request = $req;
    });

    $response = $this->post("$this->prefix/$path", ['post' => [
      'required' => 'required',
      'string' => 'abc',
      'number-1' => 0.123,
      'number-2' => 123,
      'integer-1' => 123,
      'integer-2' => -123,
      'boolean-1' => 0,
      'boolean-2' => true,
    ]]);

    $this->assertInstanceOf(Request::class, $request);
    $this->assertSame(['data' => null], $response);
    $this->assertSame([
      'required' => 'required',
      'string' => 'abc',
      'number-1' => 0.123,
      'number-2' => 123.0,
      'integer-1' => 123,
      'integer-2' => -123,
      'boolean-1' => false,
      'boolean-2' => true,
    ], $request->getParams());
  }

  public function testMissingParam(): void {
    $path = strtolower(__FUNCTION__);
    $this->registerTestingGetRoute($path);

    $response = $this->get("$this->prefix/$path");
    $this->assertSame([
      'code' => 'rest_missing_callback_param',
      'message' => 'Missing parameter(s): required',
      'data' => [
        'status' => 400,
        'params' => ['required'],
      ],
    ], $response);
  }

  public function testExtraParam(): void {
    $path = strtolower(__FUNCTION__);
    $request = null;
    $this->registerTestingGetRoute($path, function (Request $req) use (&$request) {
      $request = $req;
    });

    $this->get("$this->prefix/$path", ['query' => ['required' => 'required', 'extra' => 'extra']]);
    $this->assertInstanceOf(Request::class, $request);
    $this->assertSame($request->getParams(), ['required' => 'required']);
  }

  private function registerTestingGetRoute(string $path, callable $requestCallback = null): void {
    $api = $this->createApi($requestCallback);
    $api->registerGetRoute("mailpoet-api-testing-route/$path", Endpoint::class);
  }

  private function registerTestingPostRoute(string $path, callable $requestCallback = null): void {
    $api = $this->createApi($requestCallback);
    $api->registerPostRoute("mailpoet-api-testing-route/$path", Endpoint::class);
  }

  private function createApi(callable $requestCallback = null): API {
    // ensure REST server is initialized for endpoint registration
    rest_get_server();

    return new API(
      $this->make(EndpointContainer::class, [
        'get' => function () use ($requestCallback) {
          return new Endpoint($requestCallback);
        },
      ]),
      $this->diContainer->get(WPFunctions::class)
    );
  }
}
