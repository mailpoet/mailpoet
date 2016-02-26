<?php
namespace MailPoet\Router;
use \MailPoet\Util\Security;

use \MailPoet\Models\Subscriber;
use \MailPoet\Models\CustomField;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Segment;
use \MailPoet\Util\Helpers;


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
        if($subscriber !== false) {
          $subscriber = $subscriber
          ->withCustomFields()
          ->withSubscriptions();

          $custom_fields = array_map(function($custom_field) use($subscriber) {
            $custom_field->id = 'cf_'.$custom_field->id;
            $custom_field = $custom_field->asArray();
            $custom_field['params']['value'] = $subscriber->{$custom_field['id']};
            return $custom_field;
          }, CustomField::findMany());

          $segment_ids = Setting::getValue('subscription.segments', array());
          if(!empty($segment_ids)) {
            $segments = Segment::getPublic()
              ->whereIn('id', $segment_ids)
              ->findMany();
          } else {
            $segments = Segment::getPublic()->findMany();
          }

          $subscribed_segment_ids = Helpers::arrayColumn(
            $subscriber->subscriptions, 'id'
          );

          $segments = array_map(function($segment) use($subscribed_segment_ids) {
            return array(
              'id' => $segment->id,
              'name' => $segment->name,
              'is_checked' => in_array($segment->id, $subscribed_segment_ids)
            );
          }, $segments);

          $fields = array(
            array(
              'id' => 'email',
              'type' => 'text',
              'params' => array(
                'label' => __('Email'),
                'required' => true,
                'value' => $subscriber->email
              )
            ),
            array(
              'id' => 'first_name',
              'type' => 'text',
              'params' => array(
                'label' => __('First name'),
                'value' => $subscriber->first_name
              )
            ),
            array(
              'id' => 'last_name',
              'type' => 'text',
              'params' => array(
                'label' => __('Last name'),
                'value' => $subscriber->last_name
              )
            ),
            array(
              'id' => 'status',
              'type' => 'select',
              'params' => array(
                'label' => __('Status'),
                'values' => array(
                  array(
                    'value' => array(
                      Subscriber::STATUS_SUBSCRIBED => __('Subscribed')
                    ),
                    'is_checked' => (
                      $subscriber->status === Subscriber::STATUS_SUBSCRIBED
                    )
                  ),
                  array(
                    'value' => array(
                      Subscriber::STATUS_UNSUBSCRIBED => __('Unsubscribed')
                    ),
                    'is_checked' => (
                      $subscriber->status === Subscriber::STATUS_UNSUBSCRIBED
                    )
                  ),
                  array(
                    'value' => array(
                      Subscriber::STATUS_UNCONFIRMED => __('Unconfirmed')
                    ),
                    'is_checked' => (
                      $subscriber->status === Subscriber::STATUS_UNCONFIRMED
                    )
                  )
                )
              )
            )
          );

          $form = array_merge(
            $fields,
            $custom_fields,
            array(
              array(
                'id' => 'segment',
                'type' => 'segment',
                'params' => array(
                  'label' => __('Your lists'),
                  'values' => $segments
                )
              ),
              array(
                'id' => 'submit',
                'type' => 'submit',
                'params' => array(
                  'label' => __('Subscribe!')
                )
              )
            )
          );

          $content = \MailPoet\Form\Renderer::renderBlocks($form);
        }
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
