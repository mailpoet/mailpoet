<?php
namespace MailPoet\API;

use MailPoet\Config\Env;
use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Hooks;

if(!defined('ABSPATH')) exit;

class API {
  private $_request_api_version;
  private $_request_endpoint;
  private $_request_method;
  private $_request_token;
  private $_request_endpoint_class;
  private $_request_data = array();
  private $_endpoint_namespaces = array();
  private $_ajax_request = false;
  private $_available_api_versions = array(
      'v1'
  );
  const CURRENT_VERSION = 'v1';
  const ENDPOINTS_LOCATION = 'Endpoints';

  function __construct() {
    foreach($this->_available_api_versions as $version) {
      $this->addEndpointNamespace(
        array(
          'name' => sprintf('%s\%s', __NAMESPACE__, self::ENDPOINTS_LOCATION),
          'version' => $version
        )
      );
    }
  }

  function init() {
     // admin security token and API version
    add_action(
      'admin_head',
      array($this, 'setTokenAndAPIVersion')
    );

    // ajax (logged in users)
    add_action(
      'wp_ajax_mailpoet',
      array($this, 'setupAjax')
    );

    // ajax (logged out users)
    add_action(
      'wp_ajax_nopriv_mailpoet',
      array($this, 'setupAjax')
    );
  }

  function setupAjax() {
    $this->_ajax_request = true;
    Hooks::doAction('mailpoet_api_setup', array($this));
    $this->getRequestData($_POST);

    if($this->checkToken() === false) {
      $error_message = __('Invalid API request.', 'mailpoet');
      $error_response = $this->createErrorResponse(Error::UNAUTHORIZED, $error_message, Response::STATUS_UNAUTHORIZED);
      return $error_response;
    }

    $response = $this->processRoute();
    $response->send();
  }

  function getRequestData($data) {
    $this->_request_api_version = !empty($data['api_version']) ? $data['api_version']: false;

    $this->_request_endpoint = isset($data['endpoint'])
      ? Helpers::underscoreToCamelCase(trim($data['endpoint']))
      : null;

    // JS part of /wp-admin/customize.php does not like a 'method' field in a form widget
    $method_param_name = isset($data['mailpoet_method']) ? 'mailpoet_method' : 'method';
    $this->_request_method = isset($data[$method_param_name])
      ? Helpers::underscoreToCamelCase(trim($data[$method_param_name]))
      : null;

    $this->_request_token = isset($data['token'])
      ? trim($data['token'])
      : null;

    if(!$this->_request_endpoint || !$this->_request_method || !$this->_request_api_version) {
      $error_message = __('Invalid API request.', 'mailpoet');
      $error_response = $this->createErrorResponse(Error::BAD_REQUEST, $error_message, Response::STATUS_BAD_REQUEST);
      return $error_response;
    } else {
      foreach($this->_endpoint_namespaces as $namespace) {
        if($namespace['version'] !== $this->_request_api_version) continue;
        $endpoint_class = sprintf(
          '%s\%s\%s',
          $namespace['name'],
          $namespace['version'],
          ucfirst($this->_request_endpoint)
        );
        if(class_exists($endpoint_class)) {
          $this->_request_endpoint_class = $endpoint_class;
        }
      }
      $this->_request_data = isset($data['data'])
        ? stripslashes_deep($data['data'])
        : array();

      // remove reserved keywords from data
      if(is_array($this->_request_data) && !empty($this->_request_data)) {
        // filter out reserved keywords from data
        $reserved_keywords = array(
          'token',
          'endpoint',
          'method',
          'api_version',
          'mailpoet_method', // alias of 'method'
          'mailpoet_redirect'
        );
        $this->_request_data = array_diff_key(
          $this->_request_data,
          array_flip($reserved_keywords)
        );
      }
    }
  }

  function processRoute() {
    try {
      if(empty($this->_request_endpoint_class)) {
        throw new \Exception(__('Invalid API endpoint.', 'mailpoet'));
      }

      $endpoint = new $this->_request_endpoint_class();

      // check the accessibility of the requested endpoint's action
      // by default, an endpoint's action is considered "private"
      $permissions = $endpoint->permissions;
      if(array_key_exists($this->_request_method, $permissions) === false ||
         $permissions[$this->_request_method] !== Access::ALL
      ) {
        if($this->checkPermissions() === false) {
          $error_message = __('You do not have the required permissions.', 'mailpoet');
          $error_response = $this->createErrorResponse(Error::FORBIDDEN, $error_message, Response::STATUS_FORBIDDEN);
          return $error_response;
        }
      }

      $response = $endpoint->{$this->_request_method}($this->_request_data);
      return $response;
    } catch(\Exception $e) {
      $error_message = $e->getMessage();
      $error_response = $this->createErrorResponse(Error::BAD_REQUEST, $error_message, Response::STATUS_BAD_REQUEST);
      return $error_response;
    }
  }

  function checkPermissions() {
    return current_user_can(Env::$required_permission);
  }

  function checkToken() {
    return wp_verify_nonce($this->_request_token, 'mailpoet_token');
  }

  function setTokenAndAPIVersion() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "%s";';
    $global .= 'var mailpoet_api_version = "%s";';
    $global .= '</script>';
    echo sprintf(
      $global,
      Security::generateToken(),
      self::CURRENT_VERSION
    );
  }

  function addEndpointNamespace(array $namespace) {
    if(empty($namespace['version'])) {
      throw new \Exception(__('Namespace version is required.', 'mailpoet'));
    }
    $this->_endpoint_namespaces[] = $namespace;
  }

  function getEndpointNamespaces() {
    return $this->_endpoint_namespaces;
  }

  function getRequestedEndpointClass() {
    return $this->_request_endpoint_class;
  }

  function getRequestedAPIVersion() {
    return $this->_request_api_version;
  }

  function createErrorResponse($error_type, $error_message, $response_status) {
    $error_response = new ErrorResponse(
      array(
        $error_type => $error_message
      ),
      array(),
      $response_status
    );
    return ($this->_ajax_request === true) ? $error_response->send() : $error_response;
  }
}