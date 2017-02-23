<?php
use Codeception\Util\Stub;
use MailPoet\API\API;
use MailPoet\API\SuccessResponse;

// required to be able to use wp_delete_user()
require_once(ABSPATH.'wp-admin/includes/user.php');
require_once('APITestNamespacedEndpointStub.php');

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

    $namespace = "MailPoet\\Dummy\\Name\\Space";
    $this->api->addEndpointNamespace($namespace);
    $namespaces = $this->api->getEndpointNamespaces();

    expect($namespaces)->count(2);
    expect($namespaces[1])->equals($namespace);
  }

  function testItCanCallAddedEndpoints() {
    $namespace = "MailPoet\\Some\\Name\\Space\\Endpoints";
    $this->api->addEndpointNamespace($namespace);

    $data = array(
      'endpoint' => 'namespaced_endpoint_stub',
      'method' => 'test',
      'data' => array('test' => 'data')
    );
    $this->api->getRequestData($data);
    $response = $this->api->processRoute();

    expect($response->getData()['data'])->equals($data['data']);
  }

  function _after() {
    wp_delete_user($this->wp_user_id);
  }
}