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
    if($this->checkToken() === false) {
      $this->errorResponse(
        array('unauthorized' => __('This request is not authorized.')),
        array(),
        APIResponse::STATUS_UNAUTHORIZED
      )->send();
    }

    if($this->checkPermissions() === false) {
      $this->errorResponse(
        array('forbidden' => __('You do not have the required permissions.')),
        array(),
        APIResponse::STATUS_FORBIDDEN
      )->send();
    }

    $this->processRoute();
  }

  function setupPublic() {
    if($this->checkToken() === false) {
      $response = new APIErrorResponse(array(
        'unauthorized' => __('This request is not authorized.')
      ), APIResponse::STATUS_UNAUTHORIZED);
      $response->send();
    }

    $this->processRoute();
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

      // TODO: remove this condition once the API unification is complete
      if(is_object($response)) {
        $response->send();
      } else {
        // LEGACY API
        wp_send_json($response);
      }
    } catch(\Exception $e) {
      $this->errorResponse(array(
        $e->getMessage()
      ))->send();
    }
  }

  function setToken() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "'.Security::generateToken().'";';
    $global .= '</script>';
    echo $global;
  }

  function checkPermissions() {
    return current_user_can('manage_options');
  }

  function checkToken() {
    return (
      isset($_POST['token'])
      &&
      wp_verify_nonce($_POST['token'], 'mailpoet_token')
    );
  }

  function successResponse(
    $data = array(), $meta = array(), $status = APIResponse::STATUS_OK
  ) {

    return new APISuccessResponse($data, $meta, $status);
  }

  function errorResponse(
    $errors = array(), $meta = array(), $status = APIResponse::STATUS_NOT_FOUND
  ) {

    return new APIErrorResponse($errors, $meta, $status);
  }

  function badRequest($errors = array(), $meta = array()) {
    return new APIErrorResponse($errors, $meta, APIResponse::STATUS_BAD_REQUEST);
  }
}