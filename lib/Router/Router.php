<?php
namespace MailPoet\Router;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Router {
  public $api_request;
  public $endpoint;
  public $action;
  public $data;
  const NAME = 'mailpoet_router';
  const RESPONSE_ERROR = 404;

  function __construct($api_data = false) {
    $api_data = ($api_data) ? $api_data : $_GET;
    $this->api_request = isset($api_data[self::NAME]);
    $this->endpoint = isset($api_data['endpoint']) ?
      Helpers::underscoreToCamelCase($api_data['endpoint']) :
      false;
    $this->action = isset($api_data['action']) ?
      Helpers::underscoreToCamelCase($api_data['action']) :
      false;
    $this->data = isset($api_data['data']) ?
      self::decodeRequestData($api_data['data']) :
      false;
  }

  function init() {
    $endpoint_class = __NAMESPACE__ . "\\Endpoints\\" . ucfirst($this->endpoint);
    if(!$this->api_request) return;
    if(!$this->endpoint || !class_exists($endpoint_class)) {
      return $this->terminateRequest(self::RESPONSE_ERROR, __('Invalid router endpoint', 'mailpoet'));
    }
    $endpoint = new $endpoint_class($this->data);
    if(!method_exists($endpoint, $this->action) || !in_array($this->action, $endpoint->allowed_actions)) {
      return $this->terminateRequest(self::RESPONSE_ERROR, __('Invalid router endpoint action', 'mailpoet'));
    }
    do_action('mailpoet_conflict_resolver_router_url_query_parameters');
    return call_user_func(
      array(
        $endpoint,
        $this->action
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
}
