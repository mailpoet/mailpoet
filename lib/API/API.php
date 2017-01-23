<?php
namespace MailPoet\API;
use MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class API {
  private $_endpoint;
  private $_method;
  private $_token;

  private $_endpoint_class;
  private $_data = array();

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
    $this->getRequestData();

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

    $this->processRoute();
  }

  function getRequestData() {
    $this->_endpoint = isset($_POST['endpoint'])
      ? trim($_POST['endpoint'])
      : null;
    $this->_method = isset($_POST['method'])
      ? trim($_POST['method'])
      : null;
    $this->_token = isset($_POST['token'])
      ? trim($_POST['token'])
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
      $this->_endpoint_class = (
        __NAMESPACE__."\\Endpoints\\".ucfirst($this->_endpoint)
      );

      $this->_data = isset($_POST['data'])
        ? stripslashes_deep($_POST['data'])
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
          $error_response->send();
        }
      }

      $response = $endpoint->{$this->_method}($this->_data);
      $response->send();
    } catch(\Exception $e) {
      $error_response = new ErrorResponse(
        array($e->getCode() => $e->getMessage())
      );
      $error_response->send();
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
}
