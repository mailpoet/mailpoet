<?php

namespace MailPoet\Test\API\JSON;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\API as JSONAPI;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\APITestNamespacedEndpointStubV1;
use MailPoet\API\JSON\v2\APITestNamespacedEndpointStubV2;
use MailPoet\Config\AccessControl;
use MailPoet\DI\ContainerConfigurator;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;
use MailPoet\DI\ContainerFactory;
use MailPoet\WP\Functions as WPFunctions;

// required to be able to use wp_delete_user()
require_once(ABSPATH . 'wp-admin/includes/user.php');
require_once('APITestNamespacedEndpointStubV1.php');
require_once('APITestNamespacedEndpointStubV2.php');

class APITest extends \MailPoetTest {
  /** @var Container */
  private $container;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    // create WP user
    $this->wp_user_id = null;
    $wp_user_id = wp_create_user('WP User', 'pass', 'wp_user@mailpoet.com');
    if (is_wp_error($wp_user_id)) {
      // user already exists
      $this->wp_user_id = email_exists('wp_user@mailpoet.com');
    } else {
      $this->wp_user_id = $wp_user_id;
    }
    $container_factory = new ContainerFactory(new ContainerConfigurator());
    $this->container = $container_factory->getConfiguredContainer();
    $this->container->autowire(APITestNamespacedEndpointStubV1::class)->setPublic(true);
    $this->container->autowire(APITestNamespacedEndpointStubV2::class)->setPublic(true);
    $this->container->compile();
    $this->settings = $this->container->get(SettingsController::class);
    $this->api = new \MailPoet\API\JSON\API(
      $this->container,
      $this->container->get(AccessControl::class),
      $this->settings,
      new WPFunctions
    );
  }

  function testItCallsAPISetupAction() {
    $called = false;
    (new WPFunctions)->addAction(
      'mailpoet_api_setup',
      function($api) use (&$called) {
        $called = true;
        expect($api instanceof JSONAPI)->true();
      }
    );
    $api = Stub::makeEmptyExcept(
      $this->api,
      'setupAjax',
      array(
        'wp' => new WPFunctions,
        'processRoute' => Stub::makeEmpty(new SuccessResponse),
        'settings' => $this->container->get(SettingsController::class)
      )
    );
    $api->setupAjax();
    expect($called)->true();
  }

  function testItCanAddEndpointNamespaces() {
    expect($this->api->getEndpointNamespaces())->count(1);

    $namespace = array(
      'name' => 'MailPoet\\Dummy\\Name\\Space',
      'version' => 'v2'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);
    $namespaces = $this->api->getEndpointNamespaces();

    expect($namespaces)->count(2);
    expect($namespaces[$namespace['version']][0])->equals($namespace['name']);
  }

  function testItReturns400ErrorWhenAPIVersionIsNotSpecified() {
    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'test'
    );

    $response = $this->api->setRequestData($data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  function testItAcceptsAndProcessesAPIVersion() {
    $namespace = array(
      'name' => 'MailPoet\API\JSON\v2',
      'version' => 'v2'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v2',
      'api_version' => 'v2',
      'method' => 'test'
    );
    $this->api->setRequestData($data);

    expect($this->api->getRequestedAPIVersion())->equals('v2');
    expect($this->api->getRequestedEndpointClass())->equals(
      'MailPoet\API\JSON\v2\APITestNamespacedEndpointStubV2'
    );
  }

  function testItCallsAddedEndpoints() {
    $namespace = array(
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'test',
      'api_version' => 'v1',
      'data' => array('test' => 'data')
    );
    $this->api->setRequestData($data);
    $response = $this->api->processRoute();

    expect($response->getData()['data'])->equals($data['data']);
  }

  function testItCallsAddedEndpointsForSpecificAPIVersion() {
    $namespace = array(
      'name' => 'MailPoet\API\JSON\v2',
      'version' => 'v2'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v2',
      'api_version' => 'v2',
      'method' => 'testVersion'
    );
    $this->api->setRequestData($data);
    $response = $this->api->processRoute();
    expect($response->getData()['data'])->equals($data['api_version']);
  }

  function testItValidatesPermissionBeforeProcessingEndpointMethod() {
    $namespace = array(
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1'
    );
    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'restricted',
      'api_version' => 'v1',
      'data' => array('test' => 'data')
    );
    $api = Stub::make(
      JSONAPI::class,
      array(
        'container' => $this->container,
        'validatePermissions' => function($method, $permissions) use ($data) {
          expect($method)->equals($data['method']);
          expect($permissions)->equals(
            array(
              'global' => AccessControl::NO_ACCESS_RESTRICTION,
              'methods' => array(
                'test' => AccessControl::NO_ACCESS_RESTRICTION,
                'restricted' => AccessControl::PERMISSION_MANAGE_SETTINGS
              )
            )
          );
          return true;
        }
      )
    );
    $api->addEndpointNamespace($namespace['name'], $namespace['version']);
    $api->setRequestData($data);
    $response = $api->processRoute();
    expect($response->getData()['data'])->equals($data['data']);
  }

  function testItReturnsForbiddenResponseWhenPermissionFailsValidation() {
    $namespace = array(
      'name' => 'MailPoet\API\JSON\v1',
      'version' => 'v1'
    );
    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v1',
      'method' => 'restricted',
      'api_version' => 'v1',
      'data' => array('test' => 'data')
    );
    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array('validatePermission' => false)
    );

    $api = new JSONAPI($this->container, $access_control, $this->settings, new WPFunctions);
    $api->addEndpointNamespace($namespace['name'], $namespace['version']);
    $api->setRequestData($data);
    $response = $api->processRoute();
    expect($response->status)->equals(Response::STATUS_FORBIDDEN);
  }

  function testItValidatesGlobalPermission() {
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

    $api = new JSONAPI($this->container, $access_control, $this->settings, new WPFunctions);
    expect($api->validatePermissions(null, $permissions))->false();

    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array(
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        })
      )
    );
    $api = new JSONAPI($this->container, $access_control, $this->settings, new WPFunctions);
    expect($api->validatePermissions(null, $permissions))->true();
  }

  function testItValidatesEndpointMethodPermission() {
    $permissions = array(
      'global' => null,
      'methods' => array(
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

    $api = new JSONAPI($this->container, $access_control, $this->settings, new WPFunctions);
    expect($api->validatePermissions('test', $permissions))->false();

    $access_control = Stub::make(
      new AccessControl(new WPFunctions()),
      array(
        'validatePermission' => Expected::once(function($cap) {
          expect($cap)->equals(AccessControl::PERMISSION_MANAGE_SETTINGS);
          return true;
        })
      )
    );

    $api = new JSONAPI($this->container, $access_control, $this->settings, new WPFunctions);
    expect($api->validatePermissions('test', $permissions))->true();
  }

  function testItThrowsExceptionWhenInvalidEndpointMethodIsCalled() {
    $namespace = array(
      'name' => 'MailPoet\API\JSON\v2',
      'version' => 'v2'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'a_p_i_test_namespaced_endpoint_stub_v2',
      'api_version' => 'v2',
      'method' => 'fakeMethod'
    );
    $this->api->setRequestData($data);
    $response = $this->api->processRoute();

    expect($response->status)->equals(Response::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Invalid API endpoint method.');
  }

  function _after() {
    wp_delete_user($this->wp_user_id);
  }
}
