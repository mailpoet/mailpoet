<?php

namespace MailPoet\Router;

use MailPoet\Config\AccessControl;
use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Router {
  public $api_request;
  public $endpoint;
  public $action;
  public $data;
  public $endpoint_action;
  public $access_control;
  /** @var ContainerInterface */
  private $container;
  const NAME = 'mailpoet_router';
  const RESPONSE_ERROR = 404;
  const RESPONE_FORBIDDEN = 403;

  function __construct(AccessControl $access_control, ContainerInterface $container, $api_data = false) {
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
      [];
    $this->access_control = $access_control;
    $this->container = $container;
  }

  function init() {
    if (!$this->api_request) return;
    $endpoint_class = __NAMESPACE__ . "\\Endpoints\\" . ucfirst($this->endpoint);

    if (!$this->endpoint || !class_exists($endpoint_class)) {
      return $this->terminateRequest(self::RESPONSE_ERROR, WPFunctions::get()->__('Invalid router endpoint', 'mailpoet'));
    }

    $endpoint = $this->container->get($endpoint_class);

    if (!method_exists($endpoint, $this->endpoint_action) || !in_array($this->endpoint_action, $endpoint->allowed_actions)) {
      return $this->terminateRequest(self::RESPONSE_ERROR, WPFunctions::get()->__('Invalid router endpoint action', 'mailpoet'));
    }
    if (!$this->validatePermissions($this->endpoint_action, $endpoint->permissions)) {
      return $this->terminateRequest(self::RESPONE_FORBIDDEN, WPFunctions::get()->__('You do not have the required permissions.', 'mailpoet'));
    }
    WPFunctions::get()->doAction('mailpoet_conflict_resolver_router_url_query_parameters');
    $callback = [
      $endpoint,
      $this->endpoint_action,
    ];
    if (is_callable($callback)) {
      return call_user_func($callback, $this->data);
    }
  }

  static function decodeRequestData($data) {
    $data = json_decode(base64_decode($data), true);
    if (!is_array($data)) {
      $data = [];
    }
    return $data;
  }

  static function encodeRequestData($data) {
    return rtrim(base64_encode(json_encode($data)), '=');
  }

  static function buildRequest($endpoint, $action, $data = false) {
    $params = [
      self::NAME => '',
      'endpoint' => $endpoint,
      'action' => $action,
    ];
    if ($data) {
      $params['data'] = self::encodeRequestData($data);
    }
    return WPFunctions::get()->addQueryArg($params, WPFunctions::get()->homeUrl());
  }

  function terminateRequest($code, $message) {
    WPFunctions::get()->statusHeader($code, $message);
    exit;
  }

  function validatePermissions($endpoint_action, $permissions) {
    // validate action permission if defined, otherwise validate global permission
    return(!empty($permissions['actions'][$endpoint_action])) ?
      $this->access_control->validatePermission($permissions['actions'][$endpoint_action]) :
      $this->access_control->validatePermission($permissions['global']);
  }
}
