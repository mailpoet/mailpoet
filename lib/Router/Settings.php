<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Settings {
  function __construct() {
  }

  function get($params) {
    $data = array(
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com'
    );
    wp_send_json($params);
  }
}
