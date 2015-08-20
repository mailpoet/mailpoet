<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Widget {
  function __construct() {
  }

  function init() {
    add_action('widgets_init', array($this, 'registerWidget'));
    add_action('widgets_init', array($this, 'setupActions'));
    add_action('widgets_init', array($this, 'setupDependencies'));
  }

  function registerWidget() {
    register_widget('\MailPoet\Form\Widget');
  }

  function setupDependencies() {
    wp_enqueue_script('mailpoet_vendor',
      Env::$assets_url.'/js/vendor.js',
      array(),
      Env::$version,
      true
    );

    wp_enqueue_script('mailpoet_public',
      Env::$assets_url.'/js/public.js',
      array(),
      Env::$version,
      true
    );
  }

  function setupActions() {
    // ajax requests
    add_action(
      'wp_ajax_mailpoet_form_subscribe',
      'mailpoet_form_subscribe'
    );
    add_action(
      'wp_ajax_nopriv_mailpoet_form_subscribe',
      'mailpoet_form_subscribe'
    );
    // post request
    add_action(
      'admin_post_nopriv_mailpoet_form_subscribe',
      'mailpoet_form_subscribe'
    );
    add_action(
      'admin_post_mailpoet_form_subscribe',
      'mailpoet_form_subscribe'
    );
    /*add_action(
      'init',
      'mailpoet_form_subscribe'
    );*/
  }
}
