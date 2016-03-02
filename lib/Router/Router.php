<?php
namespace MailPoet\Router;
use \MailPoet\Util\Security;

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
      array($this, 'setupAdmin')
    );
    add_action(
      'wp_ajax_nopriv_mailpoet',
      array($this, 'setupPublic')
    );
  }

  function setupAdmin() {
    $this->verifyToken();
    $this->checkPermissions();
    return $this->processRoute();
  }

  function setupPublic() {
    $this->verifyToken();
    return $this->processRoute();
  }

  function processRoute() {
    $class = ucfirst($_POST['endpoint']);
    $endpoint =  __NAMESPACE__ . "\\" . $class;
    $method = $_POST['method'];
    $data = isset($_POST['data']) ? stripslashes_deep($_POST['data']) : array();

    try {
      $endpoint = new $endpoint();
      $response = $endpoint->$method($data);
      wp_send_json($response);
    } catch(\Exception $e) {
      error_log($e->getMessage());
      exit;
    }
  }

  function setToken() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "'.Security::generateToken().'";';
    $global .= '</script>';
    echo $global;
  }

  function checkPermissions() {
    if(!current_user_can('manage_options')) { die(); }
  }

  function verifyToken() {
    if(!wp_verify_nonce($_POST['token'], 'mailpoet_token')) { die(); }
  }
}
