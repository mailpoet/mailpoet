<?php

use Codeception\Util\Stub;
use MailPoet\Router\Front;

class FrontTest extends MailPoetTest {

  function __construct() {
    /*    $this->api_request = 'mailpoet_api';
        $this->api_endpoint = 'view_in_browser';
        $this->api_action = 'view';
        $this->api_data = base64_encode(serialize(array('data' => 'dummy data')));
        $this->url = "http://example.com/?{$this->api_request}&endpoint={$this->api_endpoint}&action={$this->api_action}&data={$this->api_data}";*/
    $this->router_data = array(
      'mailpoet_api' => '',
      'endpoint' => 'view_in_browser',
      'action' => 'view',
      'data' => base64_encode(serialize(array('data' => 'dummy data')))
    );
    $this->router = new Front($this->router_data);
  }

  /*  function testItCanGetAPIDataFromGetRequest() {
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

*/

  function testItContinuesExecutionWhenAPIRequestNotDetected() {
    $router_data = $this->router_data;
    unset($router_data['mailpoet_api']);
    $router = Stub::construct(
      new Front(),
      array($router_data),
      array(
        'terminateRequest' => function() { return 'terminated'; },
        'callEndpoint' => function() { return 'endpoint called'; }
      )
    );
    expect($router->init())->null();
  }

  function testItTerminatesRequestWhenEndpointNotFound() {
    $router_data = $this->router_data;
    $router_data['endpoint'] = 'invalidEndpoint';
    $router = Stub::construct(
      new Front(),
      array($router_data),
      array(
        'terminateRequest' => function($code, $error) {
          return array(
            $code,
            $error
          );
        },
        'callEndpointActoin' => function() { return 'endpoint called'; }
      )
    );
    expect($router->init())
      ->equals(
        array(
          404,
          'Invalid router endpoint.'
        )
      );
  }

  function testItCallsEndpointAction() {
    $router_data = $this->router_data;
    $router = Stub::construct(
      new Front(),
      array($router_data),
      array(
        'terminateRequest' => function($code, $error) {
          return array(
            $code,
            $error
          );
        },
        'callEndpointAction' => function($class, $action, $data) {
          return array(
            $class,
            $action,
            $data
          );
        }
      )
    );
    expect($router->init())
      ->equals(
        array(
          'MailPoet\Router\Endpoints\ViewInBrowser',
          'view',
          array('data' => 'dummy data')
        )
      );
  }

  function testItAbortsWhenEndpointActionNotFound() {
    $router_data = $this->router_data;
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
    expect($router->callEndpointAction())
      ->equals(
        404,
        'Invalid router action.'
      );
  }

  /*
    function testItCallsEndpointWhenItIsFound() {
      $router_data = $this->router_data;
      $router = Stub::construct(
        new Front(),
        array($router_data),
        array(
          'terminateRequest' => function() { return 'terminated'; },
          'callEndpoint' => function() { return 'endpoint called'; }
        )
      );
      expect($router->init())->equals('endpoint called');
    }*/


}