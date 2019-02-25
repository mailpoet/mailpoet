<?php

namespace MailPoet\Test\Router;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\AccessControl;
use MailPoet\DI\ContainerConfigurator;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;
use MailPoet\DI\ContainerFactory;
use MailPoet\Router\Endpoints\RouterTestMockEndpoint;
use MailPoet\Router\Router;

require_once('RouterTestMockEndpoint.php');

class RouterTest extends \MailPoetTest {
  public $access_control;
  public $router_data;
  /** @var Container */
  private $container;

  function _before() {
    parent::_before();
    $this->router_data = array(
      Router::NAME => '',
      'endpoint' => 'router_test_mock_endpoint',
      'action' => 'test',
      'data' => base64_encode(json_encode(array('data' => 'dummy data')))
    );
    $this->access_control = new AccessControl(new WPFunctions());
    $container_factory = new ContainerFactory(new ContainerConfigurator());
    $this->container = $container_factory->getConfiguredContainer();
    $this->container->register(RouterTestMockEndpoint::class)->setPublic(true);
    $this->container->compile();
    $this->router = new Router($this->access_control, $this->container, $this->router_data);
  }

  function testItCanGetAPIDataFromGetRequest() {
    $data = array('data' => 'dummy data');
    $url = 'http://example.com/?' . Router::NAME . '&endpoint=view_in_browser&action=view&data='
      . base64_encode(json_encode($data));
    parse_str(parse_url($url, PHP_URL_QUERY), $_GET);
    $router = new Router($this->access_control, $this->container);
    expect($router->api_request)->equals(true);
    expect($router->endpoint)->equals('viewInBrowser');
    expect($router->endpoint_action)->equals('view');
    expect($router->data)->equals($data);
  }

  function testItContinuesExecutionWhenAPIRequestNotDetected() {
    $router_data = $this->router_data;
    unset($router_data[Router::NAME]);
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      array($this->access_control, $this->container, $router_data)
    );
    $result = $router->init();
    expect($result)->null();
  }

  function testItTerminatesRequestWhenEndpointNotFound() {
    $router_data = $this->router_data;
    $router_data['endpoint'] = 'invalid_endpoint';
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      array($this->access_control, $this->container, $router_data),
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
      '\MailPoet\Router\Router',
      array($this->access_control, $this->container, $router_data),
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
        'Invalid router endpoint action'
      )
    );
  }

  function testItValidatesGlobalPermission() {
    $router = $this->router;

    $permissions = array(
      'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
    );
    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array(
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return false;
        })
      )
    );
    $router->access_control = $access_control;
    expect($router->validatePermissions(null, $permissions))->false();

    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array(
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        })
      )
    );
    $router->access_control = $access_control;
    expect($router->validatePermissions(null, $permissions))->true();
  }

  function testItValidatesEndpointActionPermission() {
    $router = $this->router;

    $permissions = array(
      'global' => null,
      'actions' => array(
        'test' => AccessControl::PERMISSION_MANAGE_SETTINGS
      )
    );

    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array(
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return false;
        })
      )
    );
    $router->access_control = $access_control;
    expect($router->validatePermissions('test', $permissions))->false();

    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array(
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        })
      )
    );
    $router->access_control = $access_control;
    expect($router->validatePermissions('test', $permissions))->true();
  }

  function testItValidatesPermissionBeforeProcessingEndpointAction() {
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      array($this->access_control, $this->container, $this->router_data),
      array(
        'validatePermissions' => function($action, $permissions) {
          expect($action)->equals($this->router_data['action']);
          expect($permissions)->equals(
            array(
              'global' => AccessControl::NO_ACCESS_RESTRICTION
            )
          );
          return true;
        }
      )
    );
    $result = $router->init();
    expect($result)->equals(
      array('data' => 'dummy data')
    );
  }

  function testItReturnsForbiddenResponseWhenPermissionFailsValidation() {
    $router = Stub::construct(
      '\MailPoet\Router\Router',
      array($this->access_control, $this->container, $this->router_data),
      array(
        'validatePermissions' => false,
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
        403,
        'You do not have the required permissions.'
      )
    );
  }

  function testItCallsEndpointAction() {
    $data = array('data' => 'dummy data');
    $result = $this->router->init();
    expect($result)->equals($data);
  }

  function testItExecutesUrlParameterConflictResolverAction() {
    $this->router->init();
    expect((boolean)did_action('mailpoet_conflict_resolver_router_url_query_parameters'))->true();
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
      'router_test_mock_endpoint',
      'test',
      $data
    );
    expect($result)->contains(Router::NAME . '&endpoint=router_test_mock_endpoint&action=test&data=' . $encoded_data);
  }
}
