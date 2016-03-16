<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;
use MailPoet\Subscription\Url as SubscriptionUrl;

class Subscription {
  /*
    {
      text: '<%= __('Unsubscribe') %>',-
      shortcode: 'subscription:unsubscribe',
    },
    {
      text: '<%= __('Manage subscriptions') %>',
      shortcode: 'subscription:manage',
    },
   */
  static function process(
    $action,
    $default_value = false,
    $newsletter = false,
    $subscriber = false
  ) {
    switch($action) {
      case 'unsubscribe':
        return '<a target="_blank" href="'.
          esc_attr(SubscriptionUrl::getUnsubscribeUrl($subscriber))
          .'">'.__('Unsubscribe').'</a>';
      break;

      case 'unsubscribe_url':
        return SubscriptionUrl::getUnsubscribeUrl($subscriber);
      break;

      case 'manage':
        return '<a target="_blank" href="'.
          esc_attr(SubscriptionUrl::getManageUrl($subscriber))
          .'">'.__('Manage subscription').'</a>';
      break;

      case 'manage_url':
        return SubscriptionUrl::getManageUrl($subscriber);
      break;

      default:
        return false;
      break;
    }
  }
}