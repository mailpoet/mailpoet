<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Permissions {
  function __construct() {
  }

  function set($permissions = array()) {
    $result = \MailPoet\Util\Permissions::set($permissions);
    wp_send_json($result);
  }
}
