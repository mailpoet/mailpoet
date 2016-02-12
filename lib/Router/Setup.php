<?php
namespace MailPoet\Router;
use \MailPoet\Config\Activator;

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
    } catch(\Exception $e) {
      $result = false;
    }
    return array(
      'result' => $result
    );
  }
}
