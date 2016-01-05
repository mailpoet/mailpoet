<?php
namespace MailPoet\Config;

use MailPoet\Cron\Daemon;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class PublicAPI {
  function __construct() {
    # http://example.com/?mailpoet-api&section=&action=&request_payload=
    $this->api = isset($_GET['mailpoet-api']) ? true : false;
    $this->section = isset($_GET['section']) ? $_GET['section'] : false;
    $this->action = isset($_GET['action']) ?
      Helpers::underscoreToCamelCase($_GET['action']) :
      false;
    $this->requestPayload = isset($_GET['request_payload']) ?
      json_decode(urldecode($_GET['request_payload']), true) :
      false;
  }

  function init() {
    if(!$this->api && !$this->section) return;
    $this->_checkAndCallMethod($this, $this->section, $terminate = true);
  }

  function queue() {
    try {
      $queue = new Daemon($this->requestPayload);
      $this->_checkAndCallMethod($queue, $this->action);
    } catch(\Exception $e) {
    }
  }

  private function _checkAndCallMethod($class, $method, $terminate = false) {
    if(!method_exists($class, $method)) {
      if(!$terminate) return;
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