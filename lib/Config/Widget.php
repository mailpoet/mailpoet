<?php
namespace MailPoet\Config;
use \MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class Widget {
  function __construct() {
  }

  function init() {
    if(!is_admin() && !is_login_page()) {
      add_action('widgets_init', array($this, 'registerWidget'));
      add_action('widgets_init', array($this, 'setupActions'));
      add_action('widgets_init', array($this, 'setupDependencies'));
    }
  }

  function registerWidget() {
    register_widget('\MailPoet\Form\Widget');
  }

  function setupDependencies() {
    wp_enqueue_script('mailpoet_public',
      Env::$assets_url.'/js/public.js',
      array(),
      Env::$version,
      true
    );

    wp_localize_script('mailpoet_public', 'MailPoetForm', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false),
      'token' => Security::generateToken()
    ));
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
