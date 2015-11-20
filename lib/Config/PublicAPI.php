<?php
namespace MailPoet\Config;

use MailPoet\Queue\Queue;
use Symfony\Component\Config\Definition\Exception\Exception;

if(!defined('ABSPATH')) exit;

class PublicAPI {
  function __construct() {
    # http://example.com/?mailpoet-api&section=&action=&data=
    $this->api = isset($_GET['mailpoet-api']) ? true : false;
    $this->section = isset($_GET['section']) ? $_GET['section'] : false;
    $this->action = isset($_GET['action']) ? $_GET['action'] : false;
    $this->data = isset($_GET['data']) ? $_GET['data'] : false;
  }

  function init() {
    if(!$this->api && !$this->section) return;
    if(method_exists($this, $this->section)) {
      call_user_func(
        array(
          $this,
          $this->section
        ));
    }
    else {
      header('HTTP/1.0 404 Not Found');
    }
    exit;
  }

  function queue() {
    $method = str_replace('_', '', lcfirst(ucwords($this->action, '_')));
    $queue = new Queue();
    if(method_exists($queue, $method)) {
      call_user_func(
        array(
          $queue,
          $method
        ));
    }
  }
}