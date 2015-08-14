<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Router {
  function __construct() {
  }

  function init() {
    add_action(
      'wp_ajax_mailpoet_ajax',
      array($this, 'setup')
    );
  }

  function setup() {
    //$this->setToken();
    $this->securityCheck();

    $request_method = filter_input(
      INPUT_SERVER,
      'REQUEST_METHOD',
      FILTER_SANITIZE_STRING
    );

    $endpoint = 'MailPoet\\Router\\'.ucfirst($_GET['mailpoet_endpoint']);
    $action = $_GET['mailpoet_action'];

    if(class_exists($endpoint) && method_exists($endpoint, $action)) {
      switch($request_method) {
        case 'GET':
          $params = array_diff_key(
            $_GET,
            array_flip(array('action', 'mailpoet_endpoint', 'mailpoet_action'))
          );
        break;

        case 'POST':
        default:
          $params = json_decode(file_get_contents('php://input'), true);
        break;
      }

      $route = new $endpoint();
      echo json_encode($route->$action($params));
    }

    wp_die();
  }

  function setToken() {
    $token = \wp_create_nonce('mailpoet_token');
    \wp_localize_script($token);
  }

  function securityCheck() {
    if (!\current_user_can('manage_options')) { wp_die(); }
    //if (!\wp_verify_nonce($_POST['token'])) { wp_die(); }
  }
}
