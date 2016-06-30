<?php
namespace MailPoet\Config;

use MailPoet\Cron\Daemon;
use MailPoet\Newsletter\ViewInBrowser;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscription;
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
    $this->data = $this->getAndValidateData();
  }

  function init() {
    if(!$this->api || !$this->endpoint) return;
    $this->_checkAndCallMethod($this, $this->endpoint, $terminate_request = true);
  }

  function queue() {
    $queue = new Daemon($this->data);
    $this->_checkAndCallMethod($queue, $this->action);
  }

  function subscription() {
    $subscription = new Subscription\Pages($this->action, $this->data);
    $this->_checkAndCallMethod($subscription, $this->action);
  }

  function track() {
    if($this->action === 'click') {
      $track_class = new Clicks($this->data);
    }
    if($this->action === 'open') {
      $track_class = new Opens($this->data);
    }
    if(!isset($track_class)) return;
    $track_class->track();
  }

  function viewInBrowser() {
    $viewer = new ViewInBrowser($this->data);
    $viewer->view();
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

  function getAndValidateData() {
    if (!isset($_GET['data'])) return false;
    $data = base64_decode($_GET['data']);
    if (!is_serialized($data)) {
      throw new \Exception(__('Invalid data format.'));
    }
    return unserialize($data);
  }
}