<?php

namespace MailPoet\Router;

use MailPoet\Config\AccessControl;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Router {
  public $api_request;
  public $endpoint;
  public $action;
  public $data;
  const NAME = 'mailpoet_router';
  const RESPONSE_ERROR = 404;
  const RESPONE_FORBIDDEN = 403;

  function __construct(AccessControl $access_control, $api_data = false) {
    $api_data = ($api_data) ? $api_data : $_GET;
    $this->api_request = isset($api_data[self::NAME]);
    $this->endpoint = isset($api_data['endpoint']) ?
      Helpers::underscoreToCamelCase($api_data['endpoint']) :
      false;
    $this->endpoint_action = isset($api_data['action']) ?
      Helpers::underscoreToCamelCase($api_data['action']) :
      false;
    $this->data = isset($api_data['data']) ?
      self::decodeRequestData($api_data['data']) :
      array();
    $this->access_control = $access_control;
  }

  function init() {
    $endpoint_class = __NAMESPACE__ . "\\Endpoints\\" . ucfirst($this->endpoint);
    if(!$this->api_request) return;
    if(!$this->endpoint || !class_exists($endpoint_class)) {
      return $this->terminateRequest(self::RESPONSE_ERROR, __('Invalid router endpoint', 'mailpoet'));
    }
    $endpoint = new $endpoint_class($this->data, $this->access_control);
    if(!method_exists($endpoint, $this->endpoint_action) || !in_array($this->endpoint_action, $endpoint->allowed_actions)) {
      return $this->terminateRequest(self::RESPONSE_ERROR, __('Invalid router endpoint action', 'mailpoet'));
    }
    if(!$this->validatePermissions($this->endpoint_action, $endpoint->permissions)) {
      return $this->terminateRequest(self::RESPONE_FORBIDDEN, __('You do not have the required permissions.', 'mailpoet'));
    }
    do_action('mailpoet_conflict_resolver_router_url_query_parameters');
    return call_user_func(
      array(
        $endpoint,
        $this->endpoint_action
      )
    );
  }

  static function decodeRequestData($data) {
    $data = json_decode(base64_decode($data), true);
    if(!is_array($data)) {
      $data = array();
    }
    return $data;
  }

  static function encodeRequestData($data) {
    return rtrim(base64_encode(json_encode($data)), '=');
  }

  static function buildRequest($endpoint, $action, $data = false) {
    $params = array(
      self::NAME => '',
      'endpoint' => $endpoint,
      'action' => $action,
    );
    if($data) {
      $params['data'] = self::encodeRequestData($data);
    }
    return add_query_arg($params, home_url());
  }

  function terminateRequest($code, $message) {
    status_header($code, $message);
    exit;
  }

  function validatePermissions($endpoint_action, $permissions) {
    // validate action permission if defined, otherwise validate global permission
    return(!empty($permissions['actions'][$endpoint_action])) ?
      $this->access_control->validatePermission($permissions['actions'][$endpoint_action]) :
      $this->access_control->validatePermission($permissions['global']);
  }
}