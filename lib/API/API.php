<?php
namespace MailPoet\API;

use MailPoet\Util\Helpers;
use MailPoet\Util\Security;
use MailPoet\WP\Hooks;

if(!defined('ABSPATH')) exit;

class API {
  private $_endpoint;
  private $_method;
  private $_token;

  private $_endpoint_namespaces = array();
  private $_endpoint_class;
  private $_data = array();

  function __construct() {
    $this->addEndpointNamespace(__NAMESPACE__ . "\\Endpoints");
  }

  function init() {
     // Admin Security token
    add_action(
      'admin_head',
      array($this, 'setToken')
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
    Hooks::doAction('mailpoet_api_setup', array($this));

    $this->getRequestData($_POST);

    if($this->checkToken() === false) {
      $error_response = new ErrorResponse(
        array(
          Error::UNAUTHORIZED => __('Invalid request', 'mailpoet')
        ),
        array(),
        Response::STATUS_UNAUTHORIZED
      );
      $error_response->send();
    }

    $response = $this->processRoute();
    $response->send();
  }

  function getRequestData($data) {
    $this->_endpoint = isset($data['endpoint'])
      ? Helpers::underscoreToCamelCase(trim($data['endpoint']))
      : null;
    $this->_method = isset($data['method'])
      ? Helpers::underscoreToCamelCase(trim($data['method']))
      : null;
    $this->_token = isset($data['token'])
      ? trim($data['token'])
      : null;

    if(!$this->_endpoint || !$this->_method) {
      // throw exception bad request
      $error_response = new ErrorResponse(
        array(
          Error::BAD_REQUEST => __('Invalid request', 'mailpoet')
        ),
        array(),
        Response::STATUS_BAD_REQUEST
      );
      $error_response->send();
    } else {
      foreach($this->_endpoint_namespaces as $namespace) {
        $class_name = $namespace . "\\" . ucfirst($this->_endpoint);
        if(class_exists($class_name)) {
          $this->_endpoint_class = $class_name;
        }
      }

      $this->_data = isset($data['data'])
        ? stripslashes_deep($data['data'])
        : array();

      // remove reserved keywords from data
      if(is_array($this->_data) && !empty($this->_data)) {
        // filter out reserved keywords from data
        $reserved_keywords = array(
          'token',
          'endpoint',
          'method',
          'mailpoet_redirect'
        );
        $this->_data = array_diff_key(
          $this->_data,
          array_flip($reserved_keywords)
        );
      }
    }
  }

  function processRoute() {
    try {
      if(empty($this->_endpoint_class)) {
        throw new \Exception('Invalid endpoint');
      }

      $endpoint = new $this->_endpoint_class();

      // check the accessibility of the requested endpoint's action
      // by default, an endpoint's action is considered "private"
      $permissions = $endpoint->permissions;
      if(
        array_key_exists($this->_method, $permissions) === false
        ||
        $permissions[$this->_method] !== Access::ALL
      ) {
        if($this->checkPermissions() === false) {
          $error_response = new ErrorResponse(
            array(
              Error::FORBIDDEN => __(
                'You do not have the required permissions.',
                'mailpoet'
              )
            ),
            array(),
            Response::STATUS_FORBIDDEN
          );
          return $error_response;
        }
      }

      $response = $endpoint->{$this->_method}($this->_data);
      return $response;
    } catch(\Exception $e) {
      $error_response = new ErrorResponse(
        array($e->getCode() => $e->getMessage())
      );
      return $error_response;
    }
  }

  function checkPermissions() {
    return current_user_can('manage_options');
  }

  function checkToken() {
    return wp_verify_nonce($this->_token, 'mailpoet_token');
  }

  function setToken() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "';
    $global .=  Security::generateToken();
    $global .= '";';
    $global .= '</script>';
    echo $global;
  }

  function addEndpointNamespace($namespace) {
    $this->_endpoint_namespaces[] = $namespace;
  }

  function getEndpointNamespaces() {
    return $this->_endpoint_namespaces;
  }
}
