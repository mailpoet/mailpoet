<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Widget {
  function __construct() {
  }

  function init() {
    $this->registerWidget();
    $this->setupActions();
    $this->setupDependencies();
  }

  private function registerWidget() {
    add_action('widgets_init',
      function() {
      register_widget('\MailPoet\Form\Widget');
    });
  }

  private function setupDependencies() {
    add_action('widgets_init', function() {
      $locale = \get_locale();

      wp_enqueue_script(
        'mailpoet_validation_i18n',
        Env::$assets_url.'/js/lib/validation/languages/'.
        'jquery.validationEngine-'.substr($locale, 0, 2).'.js',
        array(),
        Env::$version,
        true
      );

      wp_enqueue_script(
        'mailpoet_validation',
        Env::$assets_url.'/js/lib/validation/jquery.validationEngine.js',
        array(),
        Env::$version,
        true
      );

      wp_enqueue_style('mailpoet_validation',
        Env::$assets_url.'/css/lib/jquery.validationEngine.css',
        array(),
        Env::$version
      );

      wp_enqueue_script('mailpoet_widget',
        Env::$assets_url.'/js/widget.js',
        array(),
        Env::$version,
        true
      );

      wp_localize_script(
        'mailpoet_widget',
        'MailPoetWidget',
        array(
          'is_rtl'   => (int)(function_exists('is_rtl') && is_rtl()),
          'ajax_url' => admin_url('admin-ajax.php')
      ));
    });
  }

  private function setupActions() {
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
