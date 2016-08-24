<?php
namespace MailPoet\Router;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Front {
  public $api_request;
  public $endpoint;
  public $action;
  public $data;
  const NAME = 'mailpoet_api';
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
    $class =  __NAMESPACE__ . "\\Endpoints\\" . ucfirst($this->endpoint);

    if(!$this->api_request) return;
    if(!$this->endpoint || !class_exists($class)) {
      self::terminateRequest(self::RESPONSE_ERROR, __('Invalid router endpoint.'));
    }
    $this->callEndpoint(
      $class,
      $this->action,
      $this->data
    );
  }

  function callEndpoint($endpoint, $action, $data) {
    $endpoint = new $endpoint();
    if(!method_exists($endpoint, $action) || !in_array($action, $endpoint->allowed_actions)) {
      self::terminateRequest(self::RESPONSE_ERROR, __('Invalid router action.'));
    }
    call_user_func(
      array(
        $endpoint,
        $action
      ),
      $data
    );
  }

  static function decodeRequestData($data) {
    $data = base64_decode($data);

    if(is_serialized($data)) {
      $data = unserialize($data);
    }

    if(!is_array($data)) {
      $data = array();
    }

    return $data;
  }

  static function encodeRequestData($data) {
    return rtrim(base64_encode(serialize($data)), '=');
  }

  static function buildRequest($endpoint, $action, $data) {
    $data = self::encodeRequestData($data);
    $params = array(
      self::NAME => '',
      'endpoint' => $endpoint,
      'action' => $action,
      'data' => $data
    );
    return add_query_arg($params, home_url());
  }

  static function terminateRequest($code, $message) {
    status_header($code, $message);
    exit;
  }
}