<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

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
  static function process($action) {
    // TODO: implement
    $actions = array(
      'unsubscribe' => '',
      'manage' => '',
      'browser' => ''
    );
    return (isset($actions[$action])) ? $actions[$action] : false;
  }
}