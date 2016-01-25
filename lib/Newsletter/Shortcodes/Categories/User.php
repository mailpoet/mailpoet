<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Subscriber;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class User {
  /*
    {
      text: '<%= __('First Name') %>',
      shortcode: 'user:firstname | default:reader',
    },
    {
      text: '<%= __('Last Name') %>',
      shortcode: 'user:lastname | default:reader',
    },
    {
      text: '<%= __('Email Address') %>',
      shortcode: 'user:email',
    },
    {
      text: '<%= __('Wordpress user display name') %>',
      shortcode: 'user:displayname | default:member',
    },
    {
      text: '<%= __('Total of subscribers') %>',
      shortcode: 'user:count',
    }
   */
  static function process($action, $default_value, $newsletter = false, $subscriber) {
    if(is_object($subscriber)) {
      $subscriber = $subscriber->asArray();
    }
    switch($action) {
      case 'firstname':
        return ($subscriber) ? $subscriber['first_name'] : $default_value;
      case 'lastname':
        return ($subscriber) ? $subscriber['last_name'] : $default_value;
      case 'email':
        return ($subscriber) ? $subscriber['email'] : false;
      case 'displayname':
        if($subscriber && $subscriber['wp_user_id']) {
          $wp_user = get_userdata($subscriber['wp_user_id']);
          return $wp_user->user_login;
        };
        return $default_value;
      case 'count':
        return Subscriber::count();
      default:
        return false;
    }
  }
}