<?php
namespace MailPoet\Config;

use MailPoet\Cron\Daemon;
<<<<<<< 378f6d803a9ad45c36cbea3bc268c3c4e9e05f86
use MailPoet\Statistics\Track\Clicks;
=======
use MailPoet\Subscription;
>>>>>>> Subscription pages
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
    $this->data = (
      (isset($_GET['data']))
      ? unserialize(base64_decode($_GET['data']))
      : array()
    );
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

<<<<<<< 378f6d803a9ad45c36cbea3bc268c3c4e9e05f86
  function track() {
    try {
      if ($this->action === 'click') {
        $track_class = new Clicks($this->data);
      }
      if (!isset($track_class)) return;
      $track_class->track();
=======
  function subscription() {
    try {
      $subscription = new Subscription\Pages($this->action, $this->data);
      $this->_checkAndCallMethod($subscription, $this->action);
>>>>>>> Subscription pages
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