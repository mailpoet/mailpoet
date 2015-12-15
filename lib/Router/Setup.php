<?php
namespace MailPoet\Router;
use \MailPoet\Config\Activator;
use \MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Setup {
  function __construct() {
  }

  function reset() {
    try {
      $activator = new Activator();
      $activator->deactivate();
      $activator->activate();
      $result = true;
    } catch(Exception $e) {
      $result = false;
    }
    wp_send_json(array(
      'result' => $result
    ));
  }
}
