<?php

use Codeception\Util\Stub;
use MailPoet\Router\Router;

require_once('RouterTestMockEndpoint.php');

class FrontRouterTest extends MailPoetTest {
  public $router_data;
  public $router;

  function __construct() {
    $this->router_data = array(
      Router::NAME => '',
      'endpoint' => 'mock_endpoint',
      'action' => 'test',
      'data' => base64_encode(json_encode(array('data' => 'dummy data')))
    );
    $this->router = new Router($this->router_data);
  }

  function testItCanGetAPIDataFromGetRequest() {
    $data = array('data' => 'dummy data');
    $url = 'http://example.com/?' . Router::NAME . '&endpoint=view_in_browser&action=view&data='
      . base64_encode(json_encode($data));
    parse_str(parse_url($url, PHP_URL_QUERY), $_GET);
    $router = new Router();
    expect($router->api_request)->equals(true);
    expect($router->endpoint)->equals('viewInBrowser');
    expect($router->action)->equals('view');
    expect($router->data)->equals($data);
  }

  function testItContinuesExecutionWhenAPIRequestNotDetected() {
    $router_data = $this->router_data;
    unset($router_data[Router::NAME]);
    $router = Stub::construct(
      new Router(),
      array($router_data)
    );
    $result = $router->init();
    expect($result)->null();
  }

  function testItTerminatesRequestWhenEndpointNotFound() {
    $router_data = $this->router_data;
    $router_data['endpoint'] = 'invalid_endpoint';
    $router = Stub::construct(
      new Router(),
      array($router_data),
      array(
        'terminateRequest' => function($code, $error) {
          return array(
            $code,
            $error
          );
        }
      )
    );
    $result = $router->init();
    expect($result)->equals(
      array(
        404,
        'Invalid router endpoint'
      )
    );
  }

  function testItTerminatesRequestWhenEndpointActionNotFound() {
    $router_data = $this->router_data;
    $router_data['action'] = 'invalid_action';
    $router = Stub::construct(
      new Router(),
      array($router_data),
      array(
        'terminateRequest' => function($code, $error) {
          return array(
            $code,
            $error
          );
        }
      )
    );
    $result = $router->init();
    expect($result)->equals(
      array(
        404,
        'Invalid router endpoint action.'
      )
    );
  }

  function testItCallsEndpointAction() {
    $data = array('data' => 'dummy data');
    $result = $this->router->init();
    expect($result)->equals($data);
  }

  function testItExecutesUrlParameterConflictResolverAction() {
    $data = array('data' => 'dummy data');
    $result = $this->router->init();
    expect((boolean) did_action('mailpoet_conflict_resolver_router_url_query_parameters'))->true();
  }

  function testItCanEncodeRequestData() {
    $data = array('data' => 'dummy data');
    $result = Router::encodeRequestData($data);
    expect($result)->equals(
      rtrim(base64_encode(json_encode($data)), '=')
    );
  }

  function testItReturnsEmptyArrayWhenRequestDataIsAString() {
    $encoded_data = 'test';
    $result = Router::decodeRequestData($encoded_data);
    expect($result)->equals(array());
  }

  function testItCanDecodeRequestData() {
    $data = array('data' => 'dummy data');
    $encoded_data = rtrim(base64_encode(json_encode($data)), '=');
    $result = Router::decodeRequestData($encoded_data);
    expect($result)->equals($data);
  }

  function testItCanConvertInvalidRequestDataToArray() {
    $result = Router::decodeRequestData('some_invalid_data');
    expect($result)->equals(array());
  }

  function testItCanBuildRequest() {
    $data = array('data' => 'dummy data');
    $encoded_data = rtrim(base64_encode(json_encode($data)), '=');
    $result = Router::buildRequest(
      'mock_endpoint',
      'test',
      $data
    );
    expect($result)->contains(Router::NAME . '&endpoint=mock_endpoint&action=test&data=' . $encoded_data);
  }
}
