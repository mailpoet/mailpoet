<?php
namespace MailPoet\Config;

use MailPoet\Queue\Queue;

if(!defined('ABSPATH')) exit;

class PublicAPI {
  function __construct() {
    # http://example.com/?mailpoet-api&section=&action=&payload=
    $this->api = isset($_GET['mailpoet-api']) ? true : false;
    $this->section = isset($_GET['section']) ? $_GET['section'] : false;
    $this->action = isset($_GET['action']) ?
      str_replace('_', '', lcfirst(ucwords($_GET['action'], '_'))) :
      false;
    $this->payload = isset($_GET['payload']) ?
      json_decode(urldecode($_GET['payload']), true) :
      false;
  }

  function init() {
    if(!$this->api && !$this->section) return;
    if(method_exists($this, $this->section)) {
      call_user_func(
        array(
          $this,
          $this->section
        ));
    } else {
      header('HTTP/1.0 404 Not Found');
    }
    exit;
  }

  function queue() {
    $queue = new Queue($this->payload);
    if(method_exists($queue, $this->action)) {
      call_user_func(
        array(
          $queue,
          $this->action
        ));
    }
  }
}