<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Permissions {
  function __construct() {
  }

  function set($permissions = array()) {
    return \MailPoet\Util\Permissions::set($permissions);
  }
}
