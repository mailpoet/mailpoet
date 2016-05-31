<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\SubscriberCustomField;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Subscriber {
  static function process(
    $action,
    $default_value,
    $newsletter = false,
    $subscriber
  ) {
    switch($action) {
      case 'firstname':
        return ($subscriber) ? $subscriber['first_name'] : $default_value;
      break;
      case 'lastname':
        return ($subscriber) ? $subscriber['last_name'] : $default_value;
      break;
      case 'email':
        return ($subscriber) ? $subscriber['email'] : false;
      break;
      case 'displayname':
        if($subscriber && $subscriber['wp_user_id']) {
          $wp_user = get_userdata($subscriber['wp_user_id']);
          return $wp_user->user_login;
        }
        return $default_value;
      break;
      case 'count':
        return Subscriber::filter('subscribed')->count();
      break;
      case preg_match('/cf_(\d+)/', $action, $custom_field) ? true : false:
        if(empty($subscriber['id'])) return false;
        $custom_field = SubscriberCustomField
          ::where('subscriber_id', $subscriber['id'])
          ->where('custom_field_id', $custom_field[1])
          ->findOne();
        return ($custom_field) ? $custom_field->value : false;
      break;
      default:
        return false;
      break;
    }
  }
}