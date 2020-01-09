<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\WP\Functions as WPFunctions;

class Subscriber {
  /**
   * @param \MailPoet\Models\Subscriber|false|mixed $subscriber
   */
  public static function process(
    $shortcodeDetails,
    $newsletter,
    $subscriber
  ) {
    if ($subscriber !== false && !($subscriber instanceof SubscriberModel)) {
      return $shortcodeDetails['shortcode'];
    }
    $defaultValue = ($shortcodeDetails['action_argument'] === 'default') ?
      $shortcodeDetails['action_argument_value'] :
      '';
    switch ($shortcodeDetails['action']) {
      case 'firstname':
        return (!empty($subscriber->firstName)) ? $subscriber->firstName : $defaultValue;
      case 'lastname':
        return (!empty($subscriber->lastName)) ? $subscriber->lastName : $defaultValue;
      case 'email':
        return ($subscriber) ? $subscriber->email : false;
      case 'displayname':
        if ($subscriber && $subscriber->wpUserId) {
          $wpUser = WPFunctions::get()->getUserdata($subscriber->wpUserId);
          return $wpUser->userLogin;
        }
        return $defaultValue;
      case 'count':
        return SubscriberModel::filter('subscribed')
          ->count();
      default:
        if (preg_match('/cf_(\d+)/', $shortcodeDetails['action'], $customField) &&
          !empty($subscriber->id)
        ) {
          $customField = SubscriberCustomField
            ::where('subscriber_id', $subscriber->id)
            ->where('custom_field_id', $customField[1])
            ->findOne();
          return ($customField instanceof SubscriberCustomField) ? $customField->value : false;
        }
        return false;
    }
  }
}
