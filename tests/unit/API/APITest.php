<?php

use Codeception\Util\Stub;
use MailPoet\API\API;
use MailPoet\API\SuccessResponse;
use MailPoet\API\Response as APIResponse;

// required to be able to use wp_delete_user()
require_once(ABSPATH.'wp-admin/includes/user.php');
require_once('APITestNamespacedEndpointStubV1.php');
require_once('APITestNamespacedEndpointStubV2.php');

class APITest extends MailPoetTest {
  function _before() {
    // create WP user
    $this->wp_user_id = null;
    $wp_user_id = wp_create_user('WP User', 'pass', 'wp_user@mailpoet.com');
    if(is_wp_error($wp_user_id)) {
      // user already exists
      $this->wp_user_id = email_exists('wp_user@mailpoet.com');
    } else {
      $this->wp_user_id = $wp_user_id;
    }

    $this->api = new API();
  }

  function testItChecksPermissions() {
    // logged out user
    expect($this->api->checkPermissions())->false();

    // give administrator role to wp user
    $wp_user = get_user_by('id', $this->wp_user_id);
    $wp_user->add_role('administrator');
    wp_set_current_user($wp_user->ID, $wp_user->user_login);

    // administrator should have permission
    expect($this->api->checkPermissions())->true();
  }

  function testItCallsAPISetupAction() {
    $called = false;
    add_action(
      'mailpoet_api_setup',
      function ($api) use (&$called) {
        $called = true;
        expect($api instanceof API)->true();
      }
    );
    $api = Stub::makeEmptyExcept(
      $this->api,
      'setupAjax',
      array(
        'processRoute' => Stub::makeEmpty(new SuccessResponse)
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
      'endpoint' => 'namespaced_endpoint_stub',
      'method' => 'test'
    );

    $response = $this->api->setRequestData($data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  function testItAcceptsAndProcessesAPIVersion() {
    $namespace = array(
      'name' => 'MailPoet\API\Endpoints\v2',
      'version' => 'v2'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'namespaced_endpoint_stub',
      'api_version' => 'v2',
      'method' => 'test'
    );
    $this->api->setRequestData($data);

    expect($this->api->getRequestedAPIVersion())->equals('v2');
    expect($this->api->getRequestedEndpointClass())->equals(
      'MailPoet\API\Endpoints\v2\NamespacedEndpointStub'
    );
  }

  function testItCallsAddedEndpoints() {
    $namespace = array(
      'name' => 'MailPoet\API\Endpoints\v1',
      'version' => 'v1'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'namespaced_endpoint_stub',
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
      'name' => 'MailPoet\API\Endpoints\v2',
      'version' => 'v2'
    );
    $this->api->addEndpointNamespace($namespace['name'], $namespace['version']);

    $data = array(
      'endpoint' => 'namespaced_endpoint_stub',
      'api_version' => 'v2',
      'method' => 'testVersion'
    );
    $this->api->setRequestData($data);
    $response = $this->api->processRoute();

    expect($response->getData()['data'])->equals($data['api_version']);
  }

  function _after() {
    wp_delete_user($this->wp_user_id);
  }
}