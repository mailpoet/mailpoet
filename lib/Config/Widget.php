<?php
namespace MailPoet\Config;
use MailPoet\Models\Form;

if(!defined('ABSPATH')) exit;

class Widget {
  private $renderer = null;

  function __construct($renderer = null) {
    if($renderer !== null) {
      $this->renderer = $renderer;
    }
  }

  function init() {
    $this->registerWidget();

    if(!is_admin()) {
      $this->setupDependencies();
      $this->setupIframe();
    } else {
      add_action('widgets_admin_page', array($this, 'setupAdminWidgetPageDependencies'));
    }
  }

  function setupIframe() {
    $form_id = (isset($_GET['mailpoet_form_iframe']) ? (int)$_GET['mailpoet_form_iframe'] : 0);
    if($form_id > 0) {
      $form = Form::findOne($form_id);

      if($form !== false) {
        $form_widget = new \MailPoet\Form\Widget();
        $form_html = $form_widget->widget(array(
          'form' => $form_id,
          'form_type' => 'iframe'
        ));

        // capture javascripts
        ob_start();
        wp_print_scripts('jquery');
        wp_print_scripts('mailpoet_vendor');
        wp_print_scripts('mailpoet_public');
        $scripts = ob_get_contents();
        ob_end_clean();

        // language attributes
        $language_attributes = array();
        $is_rtl = (bool)(function_exists('is_rtl') && is_rtl());

        if($is_rtl) {
          $language_attributes[] = 'dir="rtl"';
        }

        if($lang = get_bloginfo('language')) {
          if(get_option('html_type') === 'text/html') {
            $language_attributes[] = "lang=\"$lang\"";
          }
        }

        $language_attributes = apply_filters(
          'language_attributes', implode(' ', $language_attributes)
        );

        $data = array(
          'language_attributes' => $language_attributes,
          'scripts' => $scripts,
          'form' => $form_html,
          'mailpoet_form' => array(
            'ajax_url' => admin_url('admin-ajax.php', 'absolute'),
            'is_rtl' => $is_rtl
          )
        );

        try {
          echo $this->renderer->render('form/iframe.html', $data);
        } catch(\Exception $e) {
          echo $e->getMessage();
        }
      }
      exit();
    }
  }

  function registerWidget() {
    register_widget('\MailPoet\Form\Widget');
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
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false)
    ));
  }

  function setupAdminWidgetPageDependencies() {
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

  // TODO: extract this method into an Initializer
  // - the "ajax" part might probably be useless
  // - the "post" (non-ajax) part needs to be redone properly
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
