<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;
use MailPoet\Subscription;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Link {
  /*
    {
      text: '<%= __('Unsubscribe link') %>',
      shortcode: 'global:unsubscribe',
    },
    {
      text: '<%= __('Edit subscription page link') %>',
      shortcode: 'global:manage',
    },
    {
      text: '<%= __('View in browser link') %>',
      shortcode: 'global:browser',
    }
   */
  static function process(
    $action,
    $default_value,
    $newsletter = false,
    $subscriber = false
  ) {

    $actions = array(
      'unsubscribe' =>
        '<a
          target="_blank"
          href="'.esc_attr(Subscription\Url::getUnsubscribeUrl($subscriber)).'">'.
          __('Unsubscribe').
        '</a>',
      'manage' =>
        '<a
          target="_blank"
          href="'.esc_attr(Subscription\Url::getManageUrl($subscriber)).'">'.
          __('Manage subscription').
        '</a>',
      'browser' => 'TODO'
    );
    return (isset($actions[$action])) ? $actions[$action] : false;
  }
}