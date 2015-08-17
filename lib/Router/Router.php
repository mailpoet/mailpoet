<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Router {
  function __construct() {
  }

  function init() {
    add_action(
      'admin_head',
      array($this, 'setToken')
    );
    add_action(
      'wp_ajax_mailpoet',
      array($this, 'setup')
    );
  }

  function setup() {
    $this->securityCheck();
    $class = ucfirst($_POST['endpoint']);
    $endpoint =  __NAMESPACE__ . "\\" . $class;
    $method = $_POST['method'];
    $args = $_POST['args'];
    $endpoint = new $endpoint();
    $endpoint->$method($args);
  }

  function setToken() {
    $token = wp_create_nonce('mailpoet_token');
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "' . $token . '";';
    $global .= "</script>/n";
    echo $global;
  }

  function securityCheck() {
    if (!current_user_can('manage_options')) {die();}
    if (!wp_verify_nonce($_POST['token'], 'mailpoet_token')) {die();}
  }
}
