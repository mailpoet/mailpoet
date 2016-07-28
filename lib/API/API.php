<?php
namespace MailPoet\API;

use \MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class API {
  function __construct() {
  }

  function init() {
    // security token
    add_action(
      'admin_head',
      array($this, 'setToken')
    );

    // Admin API (Ajax only)
    add_action(
      'wp_ajax_mailpoet',
      array($this, 'setupAdmin')
    );

    // Public API (Ajax)
    add_action(
      'wp_ajax_nopriv_mailpoet',
      array($this, 'setupPublic')
    );
    // Public API (Post)
    add_action(
      'admin_post_nopriv_mailpoet',
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

    $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);

    if($doing_ajax) {
      $data = isset($_POST['data']) ? stripslashes_deep($_POST['data']) : array();
    } else {
      $data = $_POST;
    }

    if(is_array($data) && !empty($data)) {
      // filter out reserved keywords from data
      $reserved_keywords = array(
        'token',
        'endpoint',
        'method',
        'mailpoet_redirect'
      );
      $data = array_diff_key($data, array_flip($reserved_keywords));
    }

    try {
      $endpoint = new $endpoint();
      $response = $endpoint->$method($data);
      wp_send_json($response);
    } catch(\Exception $e) {
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
    if(!current_user_can('manage_options')) {
      die();
    }
  }

  function verifyToken() {
    if(
      empty($_POST['token'])
      ||
      !wp_verify_nonce($_POST['token'], 'mailpoet_token')
    ) {
      die();
    }
  }
}
