<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Router {
  function __construct() {
  }

  function init() {
    add_action(
      'wp_ajax_mailpoet',
      array($this, 'setup')
    );
    $this->setToken();
  }

  function setup() {
    $this->securityCheck();
    $endpoint =  ucfirst($_POST['endpoint']);
    $action = $_POST['action'];
    $args = $_POST['args'];
    $route = new $endpoint();
    $route->$action($args);
  }

  function setToken() {
    $token = wp_create_nonce('mailpoet_token');
    wp_localize_script($token);
  }

  function securityCheck() {
    if (!current_user_can('manage_options')) {die();}
    if (!wp_verify_nonce($_POST['token'])) {die();}
  }
}
