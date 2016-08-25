<?php

use Codeception\Util\Stub;
use MailPoet\Router\Front;

require_once('FrontTestMockEndpoint.php');

class FrontTest extends MailPoetTest {
  public $router_data;
  public $router;

  function __construct() {
    $this->router_data = array(
      'mailpoet_api' => '',
      'endpoint' => 'mock_endpoint',
      'action' => 'test',
      'data' => base64_encode(serialize(array('data' => 'dummy data')))
    );
    $this->router = new Front($this->router_data);
  }

  function testItCanGetAPIDataFromGetRequest() {
    $data = array('data' => 'dummy data');
    $url = 'http://example.com/?mailpoet_api&endpoint=view_in_browser&action=view&data='
      . base64_encode(serialize($data));
    parse_str(parse_url($url, PHP_URL_QUERY), $_GET);
    $router = new Front();
    expect($router->api_request)->equals(true);
    expect($router->endpoint)->equals('viewInBrowser');
    expect($router->action)->equals('view');
    expect($router->data)->equals($data);
  }

  function testItContinuesExecutionWhenAPIRequestNotDetected() {
    $router_data = $this->router_data;
    unset($router_data['mailpoet_api']);
    $router = Stub::construct(
      new Front(),
      array($router_data)
    );
    $result = $router->init();
    expect($result)->null();
  }

  function testItTerminatesRequestWhenEndpointNotFound() {
    $router_data = $this->router_data;
    $router_data['endpoint'] = 'invalid_endpoint';
    $router = Stub::construct(
      new Front(),
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
        'Invalid router endpoint.'
      )
    );
  }

  function testItTerminatesRequestWhenEndpointActionNotFound() {
    $router_data = $this->router_data;
    $router_data['action'] = 'invalid_action';
    $router = Stub::construct(
      new Front(),
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
        'Invalid router action.'
      )
    );
  }

  function testItCallsEndpointAction() {
    $data = array('data' => 'dummy data');
    $result = $this->router->init();
    expect($result)->equals($data);
  }

  function testItCanEncodeRequestData() {
    $data = array('data' => 'dummy data');
    $result = Front::encodeRequestData($data);
    expect($result)->equals(
      rtrim(base64_encode(serialize($data)), '=')
    );
  }

  function testItReturnsEmptyArrayWhenRequestDataIsAString() {
    $encoded_data = 'test';
    $result = Front::decodeRequestData($encoded_data);
    expect($result)->equals(array());
  }

  function testItCanDecodeRequestData() {
    $data = array('data' => 'dummy data');
    $encoded_data = rtrim(base64_encode(serialize($data)), '=');
    $result = Front::decodeRequestData($encoded_data);
    expect($result)->equals($data);
  }

  function testItCanBuildRequest() {
    $data = array('data' => 'dummy data');
    $encoded_data = rtrim(base64_encode(serialize($data)), '=');
    $result = Front::buildRequest(
      'mock_endpoint',
      'test',
      $data
    );
    expect($result)->contains('?mailpoet_api&endpoint=mock_endpoint&action=test&data=' . $encoded_data);
  }
}