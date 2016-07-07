<?php
namespace MailPoet\API;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class API {
  public $api_request;
  public $endpoint;
  public $action;
  public $data;
  const API_NAME = 'mailpoet_api';
  const ENDPOINT_NAMESCAPE = '\MailPoet\API\Endpoints\\';

  function __construct() {
    $this->api_request = isset($_GET[self::API_NAME]) ? true : false;
    $this->endpoint = isset($_GET['endpoint']) ?
      Helpers::underscoreToCamelCase($_GET['endpoint']) :
      false;
    $this->endpoint = self::ENDPOINT_NAMESCAPE . ucfirst($this->endpoint);
    $this->action = isset($_GET['action']) ?
      Helpers::underscoreToCamelCase($_GET['action']) :
      false;
    $this->data = isset($_GET['data']) ?
      self::decodeRequestData($_GET['data']) :
      false;
  }

  function init() {
    if(!$this->api_request) return;
    if(!$this->endpoint) {
      $this->terminateRequest(404, __('Invalid API endpoint.'));
    }
    $this->callEndpoint($this->endpoint, $this->action, $this->data);
  }

  function callEndpoint($endpoint, $action, $data) {
    if(!method_exists($endpoint, $action)) {
      $this->terminateRequest(404, __('Invalid API action.'));
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
      self::terminateRequest(404, __('Invalid API data format.'));
  }

  static function encodeRequestData($data) {
    return rtrim(base64_encode(serialize($data)), '=');
  }

  static function buildRequest($endpoint, $action, $data) {
    $data = base64_encode(serialize($data));
    $params = array(
      self::API_NAME => '',
      'endpoint' => $endpoint,
      'action' => $action,
      'data' => $data
    );
    return add_query_arg($params, home_url());
  }

  function terminateRequest($code, $message) {
    status_header($code, $message);
    exit;
  }
}