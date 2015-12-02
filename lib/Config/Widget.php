<?php
namespace MailPoet\Config;
use \MailPoet\Models\Subscriber;
use \MailPoet\Util\Security;

if(!defined('ABSPATH')) exit;

class Widget {
  function __construct() {
  }

  function init() {
    add_action('widgets_init', array($this, 'registerWidget'));

    if(!is_admin()) {
      //$this->setupActions();
      add_action('widgets_init', array($this, 'setupDependencies'));
    } else {
      add_action('widgets_init', array($this, 'setupAdminDependencies'));
    }
  }

  function registerWidget() {
    register_widget('\MailPoet\Form\Widget');

    // subscribers count shortcode
    add_shortcode('mailpoet_subscribers_count', array(
      $this, 'getSubscribersCount'
    ));
    add_shortcode('wysija_subscribers_count', array(
      $this, 'getSubscribersCount'
    ));
  }

  function getSubscribersCount($params) {
    return Subscriber::filter('subscribed')->count();
  }

  function setupDependencies() {
    wp_enqueue_style('mailpoet_public', Env::$assets_url.'/css/public.css');

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

    wp_localize_script('mailpoet_public', 'MailPoetForm', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false),
      'token' => Security::generateToken()
    ));
  }

  function setupAdminDependencies() {
    if(
      empty($_GET['page'])
      or
      isset($_GET['page']) && strpos($_GET['page'], 'mailpoet') === false
    ) {
      wp_enqueue_script('mailpoet_vendor',
        Env::$assets_url.'/js/vendor.js',
        array(),
        Env::$version,
        true
      );

      wp_enqueue_script('mailpoet_admin',
        Env::$assets_url.'/js/mailpoet.js',
        array(),
        Env::$version,
        true
      );
    }
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
    add_action(
      'init',
      'mailpoet_form_subscribe'
    );
  }
}