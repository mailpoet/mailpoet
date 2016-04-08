<?php
namespace MailPoet\Config;

use MailPoet\Cron\Daemon;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class PublicAPI {
  public $api;
  public $endpoint;
  public $action;
  public $data;

  function __construct() {
    # http://example.com/?mailpoet&endpoint=&action=&data=
    $this->api = isset($_GET['mailpoet']) ? true : false;
    $this->endpoint = isset($_GET['endpoint']) ?
      Helpers::underscoreToCamelCase($_GET['endpoint']) :
      false;
    $this->action = isset($_GET['action']) ?
      Helpers::underscoreToCamelCase($_GET['action']) :
      false;
    $this->data = isset($_GET['data']) ?
      unserialize(base64_decode($_GET['data'])) :
      false;
  }

  function init() {
    if(!$this->api && !$this->endpoint) return;
    $this->_checkAndCallMethod($this, $this->endpoint, $terminate_request = true);
  }

  function queue() {
    try {
      $queue = new Daemon($this->data);
      $this->_checkAndCallMethod($queue, $this->action);
    } catch(\Exception $e) {
    }
  }

  private function _checkAndCallMethod($class, $method, $terminate_request = false) {
    if(!method_exists($class, $method)) {
      if(!$terminate_request) return;
      header('HTTP/1.0 404 Not Found');
      exit;
    }
    call_user_func(
      array(
        $class,
        $method
      )
    );
  }
}