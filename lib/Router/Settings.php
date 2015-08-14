<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get($params = array()) {
    $data = array(
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com'
    );
    return $data;
  }
}
