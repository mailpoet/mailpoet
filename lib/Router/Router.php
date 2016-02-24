<?php
namespace MailPoet\Router;
use \MailPoet\Util\Security;
use \MailPoet\Models\Subscriber;

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
    $this->setupPublic();
  }

  function setup() {
    $this->securityCheck();
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

  function setupPublic() {
    if(isset($_GET['mailpoet_page'])) {
      $mailpoet_page = $_GET['mailpoet_page'];

      add_filter('wp_title', array($this,'setWindowTitle'));
      add_filter('the_title', array($this,'setPageTitle'));
      add_filter('the_content', array($this,'setPageContent'));
    }
  }

  function setWindowTitle() {

  }

  function setPageTitle($title) {
    $action = (isset($_GET['mailpoet_action']))
      ? $_GET['mailpoet_action']
      : null;

    switch($action) {
      case 'confirm':
        $token = (isset($_GET['mailpoet_token']))
          ? $_GET['mailpoet_token']
          : null;
        $email = (isset($_GET['mailpoet_email']))
          ? $_GET['mailpoet_email']
          : null;

        if(empty($token) || empty($token)) {
          $title = sprintf(
            __("You've subscribed to: %s"),
            'demo'
          );
        } else {
          // check token validity
          if(md5(AUTH_KEY.$email) !== $token) {
            $title = __('Your confirmation link expired, please subscribe again.');
          } else {
            $subscriber = Subscriber::findOne($email);
            if($subscriber !== false) {
              if($subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
                $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
                $subscriber->save();
              }

              $segments = $subscriber->segments()->findMany();

              $segment_names = array_map(function($segment) {
                return $segment->name;
              }, $segments);

              $title = sprintf(
                __("You've subscribed to: %s"),
                join(', ', $segment_names)
              );
            }
          }
        }
      break;
      case 'manage':
        // TODO
      break;
      case 'unsubscribe':
        // TODO
      break;
    }
    return $title;
  }

  function setPageContent($content) {

    return __(
      "Yup, we've added you to our list. ".
      "You'll hear from us shortly."
    );

  }

  function setToken() {
    $global = '<script type="text/javascript">';
    $global .= 'var mailpoet_token = "'.Security::generateToken().'";';
    $global .= '</script>';
    echo $global;
  }

  function securityCheck() {
    if(!current_user_can('manage_options')) { die(); }
    if(!wp_verify_nonce($_POST['token'], 'mailpoet_token')) { die(); }
  }
}
