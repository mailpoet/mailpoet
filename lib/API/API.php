<?php
namespace MailPoet\API;
use \MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class API {
  private $_endpoint;
  private $_method;
  private $_token;

  private $_endpoint_class;
  private $_data = array();

  function init() {
    // security token
    add_action(
      'admin_head',
      array($this, 'setToken')
    );

    // Admin API (Ajax only)
    add_action(
      'wp_ajax_mailpoet',
      array($this, 'setupAdmin')
    );

    // Public API (Ajax)
    add_action(
      'wp_ajax_nopriv_mailpoet',
      array($this, 'setupPublic')
    );
  }

  function setupAdmin() {
    $this->getRequestData();
    $this->checkToken();
    $this->checkPermissions();
    $this->processRoute();
  }

  function setupPublic() {
    $this->getRequestData();
    $this->checkToken();
    $this->processRoute();
  }

  function getRequestData() {
    $this->_endpoint = isset($_POST['endpoint']) ? trim($_POST['endpoint']) : null;
    $this->_method = (isset($_POST['method']))
      ? trim($_POST['method'])
      : null;
    $this->_token = (isset($_POST['token']))
      ? trim($_POST['token'])
      : null;

    if(!$this->_endpoint || !$this->_method || !$this->_token) {
      // throw exception bad request
      $error_response = new ErrorResponse(
        array(
          Error::BAD_REQUEST => __('Invalid request.', 'mailpoet')
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
    $has_permission = current_user_can('manage_options');

    if($has_permission === false) {
      $error_response = new ErrorResponse(
        array(
          Error::FORBIDDEN => __('You do not have the required permissions.', 'mailpoet')
        ),
        array(),
        Response::STATUS_FORBIDDEN
      );
      $error_response->send();
    }
  }

  function checkToken() {
    $action = $this->_endpoint.'_'.$this->_method;

    $is_valid_token = wp_verify_nonce($this->_token, $action);

    if($is_valid_token === false) {
      $error_response = new ErrorResponse(
        array(
          Error::UNAUTHORIZED => __('Invalid request.', 'mailpoet')
        ),
        array(),
        Response::STATUS_UNAUTHORIZED
      );
      $error_response->send();
    }
  }

  function setToken() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "'.Security::generateToken().'";';
    $global .= '</script>';
    echo $global;
  }
}