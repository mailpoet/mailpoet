<?php
namespace MailPoet\Config;

use MailPoet\ImportExport\BootStrapMenu;
use \MailPoet\Models\Segment;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Form;
use \MailPoet\Form\Block;
use \MailPoet\Form\Renderer as FormRenderer;
use \MailPoet\Settings\Hosts;
use \MailPoet\Settings\Pages;
use \MailPoet\Settings\Charsets;
use \MailPoet\Util\Permissions;
use \MailPoet\Util\DKIM;

if(!defined('ABSPATH')) exit;

class Menu {
  function __construct($renderer, $assets_url) {
    $this->renderer = $renderer;
    $this->assets_url = $assets_url;
  }

  function init() {
    add_action(
      'admin_menu',
      array($this, 'setup')
    );
  }

  function setup() {
    add_menu_page(
      'MailPoet',
      'MailPoet',
      'manage_options',
      'mailpoet',
      array($this, 'welcome'),
      $this->assets_url . '/img/menu_icon.png',
      30
    );
    add_submenu_page(
      'mailpoet',
      __('Newsletters'),
      __('Newsletters'),
      'manage_options',
      'mailpoet-newsletters',
      array($this, 'newsletters')
    );
    add_submenu_page(
      'mailpoet',
      __('Forms'),
      __('Forms'),
      'manage_options',
      'mailpoet-forms',
      array($this, 'forms')
    );
    add_submenu_page(
      'mailpoet',
      __('Subscribers'),
      __('Subscribers'),
      'manage_options',
      'mailpoet-subscribers',
      array($this, 'subscribers')
    );
    add_submenu_page(
      'mailpoet',
      __('Segments'),
      __('Segments'),
      'manage_options',
      'mailpoet-segments',
      array($this, 'segments')
    );
    add_submenu_page(
      'mailpoet',
      __('Settings'),
      __('Settings'),
      'manage_options',
      'mailpoet-settings',
      array($this, 'settings')
    );
    add_submenu_page(
      null,
      __('Import'),
      __('Import'),
      'manage_options',
      'mailpoet-import',
      array($this, 'import')
    );
    add_submenu_page(
      null,
      __('Export'),
      __('Export'),
      'manage_options',
      'mailpoet-export',
      array($this, 'export')
    );
    // add_submenu_page(
    //   'mailpoet',
    //   __('Newsletter editor'),
    //   __('Newsletter editor'),
    //   'manage_options',
    //   'mailpoet-newsletter-editor',
    //   array($this, 'newletterEditor')
    // );
    $this->registered_pages();
  }

  function registered_pages() {
    global $_registered_pages;
    $pages = array(
      'mailpoet-welcome' => array($this, 'welcome'),
      'mailpoet-form-editor' => array($this, 'formEditor'),
      'mailpoet-newsletter-editor' => array($this, 'newletterEditor')
    );
    foreach($pages as $menu_slug => $callback) {
      $hookname = get_plugin_page_hookname($menu_slug, null);
      if(!empty($hookname)) {
        add_action($hookname, $callback);
      }
      $_registered_pages[$hookname] = true;
    }
  }

  function home() {
    $data = array();
    echo $this->renderer->render('index.html', $data);
  }

  function welcome() {
    $data = array(
      'settings' => Setting::getAll(),
      'current_user' => wp_get_current_user()
    );

    echo $this->renderer->render('welcome.html', $data);
  }

  function settings() {
    $settings = Setting::getAll();

    // dkim: check if public/private keys have been generated
    if(
      empty($settings['dkim'])
      or empty($settings['dkim']['public_key'])
      or empty($settings['dkim']['private_key'])
    ) {
      // generate public/private keys
      $keys = DKIM::generateKeys();
      $settings['dkim'] = array(
        'public_key' => $keys['public'],
        'private_key' => $keys['private'],
        'domain' => preg_replace('/^www\./', '', $_SERVER['SERVER_NAME'])
      );
    }

    $data = array(
      'settings' => $settings,
      'segments' => Segment::getPublished()->findArray(),
      'pages' => Pages::getAll(),
      'flags' => $this->_getFlags(),
      'charsets' => Charsets::getAll(),
      'current_user' => wp_get_current_user(),
      'permissions' => Permissions::getAll(),
      'hosts' => array(
        'web' => Hosts::getWebHosts(),
        'smtp' => Hosts::getSMTPHosts()
      )
    );

    echo $this->renderer->render('settings.html', $data);
  }

  private function _getFlags() {
    // flags (available features on WP install)
    $flags = array();

    if(is_multisite()) {
      // get multisite registration option
      $registration = apply_filters(
        'wpmu_registration_enabled',
        get_site_option('registration', 'all')
      );

      // check if users can register
      $flags['registration_enabled'] =
        !(in_array($registration, array('none', 'blog')));
    } else {
      // check if users can register
      $flags['registration_enabled'] =
        (bool)get_option('users_can_register', false);
    }

    return $flags;
  }

  function subscribers() {
    $data = array();

    $data['segments'] = Segment::findArray();

    echo $this->renderer->render('subscribers.html', $data);
  }

  function segments() {
    $data = array();
    echo $this->renderer->render('segments.html', $data);
  }

  function forms() {
    $data = array();
    $data['segments'] = Segment::findArray();

    echo $this->renderer->render('forms.html', $data);
  }

  function newsletters() {
    global $wp_roles;

    $data = array();

    $data['segments'] = Segment::findArray();
    $settings = Setting::findArray();
    $data['settings'] = array();
    foreach($settings as $setting) {
      $data['settings'][$setting['name']] = $setting['value'];
    }
    $data['roles'] = $wp_roles->get_names();
    echo $this->renderer->render('newsletters.html', $data);
  }

  function newletterEditor() {
    $data = array();
    wp_enqueue_media();
    wp_enqueue_script('tinymce-wplink', includes_url('js/tinymce/plugins/wplink/plugin.js'));
    wp_enqueue_style('editor', includes_url('css/editor.css'));
    echo $this->renderer->render('newsletter/form.html', $data);
  }

  function import() {
    $import = new BootStrapMenu('import');
    $data = $import->bootstrap();
    echo $this->renderer->render('import.html', $data);
  }

  function export() {
    $export = new BootStrapMenu('export');
    $data = $export->bootstrap();
    echo $this->renderer->render('export.html', $data);
  }

  function formEditor() {
    $id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $form = Form::findOne($id);
    if($form !== false) {
      $form = $form->asArray();
    }

    $data = array(
      'form' => $form,
      'pages' => Pages::getAll(),
      'segments' => Segment::getPublished()->findArray(),
      'styles' => FormRenderer::getStyles($form),
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats()
    );

    echo $this->renderer->render('form/editor.html', $data);
  }
}