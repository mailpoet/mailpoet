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

  function getSubscriber() {
    $token = (isset($_GET['mailpoet_token']))
      ? $_GET['mailpoet_token']
      : null;
    $email = (isset($_GET['mailpoet_email']))
      ? $_GET['mailpoet_email']
      : null;

    if(empty($token) || empty($email)) {
      $subscriber = Subscriber::create();
      $subscriber->email = 'demo@mailpoet.com';
      return $subscriber;
    }

    if(md5(AUTH_KEY.$email) === $token) {
      $subscriber = Subscriber::findOne($email);
      if($subscriber !== false) {
        return $subscriber;
      }
    }
    return false;
  }

  function setPageTitle($title) {
    $action = (isset($_GET['mailpoet_action']))
      ? $_GET['mailpoet_action']
      : null;

    // get subscriber
    $subscriber = $this->getSubscriber();

    switch($action) {
      case 'confirm':
        if($subscriber === false) {
          $title = __('Your confirmation link expired, please subscribe again.');
        } else {
          if($subscriber->email === 'demo@mailpoet.com') {
            $segment_names = array('demo 1', 'demo 2');
          } else {
            if($subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
              $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
              $subscriber->save();
            }

            $segments = $subscriber->segments()->findMany();

            $segment_names = array_map(function($segment) {
              return $segment->name;
            }, $segments);
          }

          $title = sprintf(
            __("You've subscribed to: %s"),
            join(', ', $segment_names)
          );
        }
      break;
      case 'edit':
        if($subscriber !== false) {
          $title = sprintf(
            __('Edit your subscriber profile: %s'),
            $subscriber->email
          );
        }
      break;
      case 'unsubscribe':
        if($subscriber !== false) {
          if($subscriber->status !== Subscriber::STATUS_UNSUBSCRIBED) {
            $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
            $subscriber->save();
          }
        }
        $title = __("You've unsubscribed!");
      break;
    }
    return $title;
  }

  function setPageContent($content) {
    $action = (isset($_GET['mailpoet_action']))
      ? $_GET['mailpoet_action']
      : null;

    $subscriber = $this->getSubscriber();

    switch($action) {
      case 'confirm':
        $content = __(
          "Yup, we've added you to our list. ".
          "You'll hear from us shortly."
        );
      break;
      case 'edit':
        $subscriber = $subscriber
        ->withCustomFields()
        ->withSubscriptions();
        $content = 'TODO';
      break;
      case 'unsubscribe':
        $content = '<p>'.__("Great, you'll never hear from us again!").'</p>';
        if($subscriber !== false) {
          $content .= '<p><strong>'.
            str_replace(
              array('[link]', '[/link]'),
              array('<a href="'.$subscriber->getConfirmationUrl().'">', '</a>'),
              __('You made a mistake? [link]Undo unsubscribe.[/link]')
            ).
          '</strong></p>';
        }
      break;
    }
    return $content;
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
