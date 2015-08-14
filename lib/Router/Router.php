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
    $this->securityCheck();

    $endpoint = 'MailPoet\\Router\\'.ucfirst($_GET['mailpoet_endpoint']);
    $action = $_GET['mailpoet_action'];

    if(class_exists($endpoint) && method_exists($endpoint, $action)) {

      $request_method = filter_input(
        INPUT_SERVER,
        'REQUEST_METHOD',
        FILTER_SANITIZE_STRING
      );

      switch($request_method) {
        case 'GET':
          $params = array_diff_key(
            $_GET,
            array_flip(array(
              'action',
              'mailpoet_endpoint',
              'mailpoet_action',
              'mailpoet_nonce'
            ))
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

  function securityCheck($action = 'mailpoet') {
    if(!current_user_can('manage_options')) { wp_die(); }
  }
}
