<?php
namespace MailPoet\API;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class API {
  public $api_request;
  public $endpoint;
  public $action;
  public $data;
  const NAME = 'mailpoet_api';
  const ENDPOINT_NAMESPACE = '\MailPoet\API\Endpoints\\';
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
    $endpoint = self::ENDPOINT_NAMESPACE . ucfirst($this->endpoint);
    if(!$this->api_request) return;
    if(!$this->endpoint || !class_exists($endpoint)) {
      self::terminateRequest(self::RESPONSE_ERROR, __('Invalid API endpoint.'));
    }
    $this->callEndpoint(
      $endpoint,
      $this->action,
      $this->data
    );
  }

  function callEndpoint($endpoint, $action, $data) {
    if(!method_exists($endpoint, $action)) {
      self::terminateRequest(self::RESPONSE_ERROR, __('Invalid API action.'));
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
    return (is_serialized($data)) ?
      unserialize($data) :
      self::terminateRequest(self::RESPONSE_ERROR, __('Invalid API data format.'));
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