<?php
namespace MailPoet\Config;

use MailPoet\Cron\Daemon;
use MailPoet\Newsletter\Viewer\ViewInBrowser;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscription;
use MailPoet\Statistics\Track\Clicks;
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
      $_GET['data'] :
      false;
  }

  function init() {
    if(!$this->api && !$this->endpoint) return;
    $this->_checkAndCallMethod($this, $this->endpoint, $terminate_request = true);
  }

  function queue() {
    try {
      $queue = new Daemon($this->_decodeData());
      $this->_checkAndCallMethod($queue, $this->action);
    } catch(\Exception $e) {
    }
  }

  function subscription() {
    try {
      $subscription = new Subscription\Pages($this->action, $this->_decodeData());
      $this->_checkAndCallMethod($subscription, $this->action);
    } catch(\Exception $e) {
    }
  }

  function track() {
    try {
      if($this->action === 'click') {
        $track_class = new Clicks($this->data);
      }
      if($this->action === 'open') {
        $track_class = new Opens($this->data);
      }
      if(!isset($track_class)) return;
      $track_class->track();
    } catch(\Exception $e) {
    }
  }

  function viewInBrowser() {
    try {
      $viewer = new ViewInBrowser($this->_decodeData());
      $viewer->view();
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

  private function _decodeData() {
    if($this->data !== false) {
      return unserialize(base64_decode($this->data));
    } else {
      return array();
    }
  }
}