<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Subscriber {
  /**
   * @param \MailPoet\Models\Subscriber|false|mixed $subscriber
   */
  static function process(
    $shortcode_details,
    $newsletter,
    $subscriber
  ) {
    if ($subscriber !== false && !is_object($subscriber)) return $shortcode_details['shortcode'];
    $default_value = ($shortcode_details['action_argument'] === 'default') ?
      $shortcode_details['action_argument_value'] :
      '';
    switch ($shortcode_details['action']) {
      case 'firstname':
        return (!empty($subscriber->first_name)) ? $subscriber->first_name : $default_value;
      case 'lastname':
        return (!empty($subscriber->last_name)) ? $subscriber->last_name : $default_value;
      case 'email':
        return ($subscriber) ? $subscriber->email : false;
      case 'displayname':
        if ($subscriber && $subscriber->wp_user_id) {
          $wp_user = WPFunctions::get()->getUserdata($subscriber->wp_user_id);
          return $wp_user->user_login;
        }
        return $default_value;
      case 'count':
        return SubscriberModel::filter('subscribed')
          ->count();
      default:
        if (preg_match('/cf_(\d+)/', $shortcode_details['action'], $custom_field) &&
          !empty($subscriber->id)
        ) {
          $custom_field = SubscriberCustomField
            ::where('subscriber_id', $subscriber->id)
            ->where('custom_field_id', $custom_field[1])
            ->findOne();
          return ($custom_field) ? $custom_field->value : false;
        }
        return false;
    }
  }
}
