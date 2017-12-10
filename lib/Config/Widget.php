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
    wp_enqueue_style(
      'mailpoet_public',
      Env::$assets_url . '/css/' . $this->renderer->getCssAsset('public.css')
    );

    wp_enqueue_script(
      'mailpoet_vendor',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('vendor.js'),
      array(),
      Env::$version,
      true
    );

    wp_enqueue_script(
      'mailpoet_public',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('public.js'),
      array('jquery'),
      Env::$version,
      true
    );

    wp_localize_script('mailpoet_public', 'MailPoetForm', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false)
    ));

    $ajax_failed_error_message = __('An error has happened while performing a request, please try again later.');
    $inline_script = <<<EOL
function initMailpoetTranslation() {
  if(typeof MailPoet !== 'undefined') {
    MailPoet.I18n.add('ajaxFailedErrorMessage', '%s')
  } else {
    setTimeout(initMailpoetTranslation, 250);
  }
}
setTimeout(initMailpoetTranslation, 250);
EOL;
    wp_add_inline_script(
      'mailpoet_public',
      sprintf($inline_script, $ajax_failed_error_message),
      'after'
    );
  }

  function setupAdminWidgetPageDependencies() {
    wp_enqueue_script(
      'mailpoet_vendor',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('vendor.js'),
      array(),
      Env::$version,
      true
    );

    wp_enqueue_script(
      'mailpoet_admin',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('mailpoet.js'),
      array(),
      Env::$version,
      true
    );
  }
}
