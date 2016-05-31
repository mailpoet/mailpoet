<?php
namespace MailPoet\Config;

use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Settings\Charsets;
use MailPoet\Settings\Hosts;
use MailPoet\Settings\Pages;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Util\DKIM;
use MailPoet\Util\Permissions;
use MailPoet\Listing;
use MailPoet\WP\DateTime;

if(!defined('ABSPATH')) exit;

class Menu {
  function __construct($renderer, $assets_url) {
    $this->renderer = $renderer;
    $this->assets_url = $assets_url;
  }

  function init() {
    add_action(
      'admin_menu',
      array(
        $this,
        'setup'
      )
    );
  }

  function setup() {
    add_menu_page(
      'MailPoet',
      'MailPoet',
      'manage_options',
      'mailpoet',
      array(
        $this,
        'home'
      ),
      $this->assets_url . '/img/menu_icon.png',
      30
    );
    $newsletters_page = add_submenu_page(
      'mailpoet',
      $this->setPageTitle(__('Newsletters')),
      __('Newsletters'),
      'manage_options',
      'mailpoet-newsletters',
      array(
        $this,
        'newsletters'
      )
    );

    // add limit per page to screen options
    add_action('load-'.$newsletters_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of newsletters per page',
          'newsletters per page (screen options)'
        ),
        'option' => 'mailpoet_newsletters_per_page'
      ));
    });

    $forms_page = add_submenu_page(
      'mailpoet',
      $this->setPageTitle(__('Forms')),
      __('Forms'),
      'manage_options',
      'mailpoet-forms',
      array(
        $this,
        'forms'
      )
    );
    // add limit per page to screen options
    add_action('load-'.$forms_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of forms per page',
          'forms per page (screen options)'
        ),
        'option' => 'mailpoet_forms_per_page'
      ));
    });

    $subscribers_page = add_submenu_page(
      'mailpoet',
      $this->setPageTitle(__('Subscribers')),
      __('Subscribers'),
      'manage_options',
      'mailpoet-subscribers',
      array(
        $this,
        'subscribers'
      )
    );
    // add limit per page to screen options
    add_action('load-'.$subscribers_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of subscribers per page',
          'subscribers per page (screen options)'
        ),
        'option' => 'mailpoet_subscribers_per_page'
      ));
    });

    $segments_page = add_submenu_page(
      'mailpoet',
      $this->setPageTitle(__('Segments')),
      __('Segments'),
      'manage_options',
      'mailpoet-segments',
      array(
        $this,
        'segments'
      )
    );

    // add limit per page to screen options
    add_action('load-'.$segments_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of segments per page',
          'segments per page (screen options)'
        ),
        'option' => 'mailpoet_segments_per_page'
      ));
    });

    add_submenu_page(
      'mailpoet',
      $this->setPageTitle( __('Settings')),
      __('Settings'),
      'manage_options',
      'mailpoet-settings',
      array(
        $this,
        'settings'
      )
    );
    add_submenu_page(
      'admin.php?page=mailpoet-subscribers',
      $this->setPageTitle( __('Import')),
      __('Import'),
      'manage_options',
      'mailpoet-import',
      array(
        $this,
        'import'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Export')),
      __('Export'),
      'manage_options',
      'mailpoet-export',
      array(
        $this,
        'export'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Welcome')),
      __('Welcome'),
      'manage_options',
      'mailpoet-welcome',
      array(
        $this,
        'welcome'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Update')),
      __('Update'),
      'manage_options',
      'mailpoet-update',
      array(
        $this,
        'update'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Form')),
      __('Form editor'),
      'manage_options',
      'mailpoet-form-editor',
      array(
        $this,
        'formEditor'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Newsletter')),
      __('Newsletter editor'),
      'manage_options',
      'mailpoet-newsletter-editor',
      array(
        $this,
        'newletterEditor'
      )
    );

    add_submenu_page(
      'mailpoet',
      $this->setPageTitle(__('Cron')),
      __('Cron'),
      'manage_options',
      'mailpoet-cron',
      array(
        $this,
        'cron'
      )
    );
  }

  function home() {
    $data = array();
    echo $this->renderer->render('index.html', $data);
  }

  function welcome() {
    if((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;

    global $wp;
    $current_url = home_url(add_query_arg($wp->query_string, $wp->request));
    $redirect_url =
      (!empty($_GET['mailpoet_redirect']))
        ? urldecode($_GET['mailpoet_redirect'])
        : wp_get_referer();

    if(
      $redirect_url === $current_url
      or
      strpos($redirect_url, 'mailpoet') === false
    ) {
      $redirect_url = admin_url('admin.php?page=mailpoet');
    }

    $data = array(
      'settings' => Setting::getAll(),
      'current_user' => wp_get_current_user(),
      'redirect_url' => $redirect_url
    );
    echo $this->renderer->render('welcome.html', $data);
  }

  function update() {
    global $wp;
    $current_url = home_url(add_query_arg($wp->query_string, $wp->request));
    $redirect_url =
      (!empty($_GET['mailpoet_redirect']))
        ? urldecode($_GET['mailpoet_redirect'])
        : wp_get_referer();

    if(
      $redirect_url === $current_url
      or
      strpos($redirect_url, 'mailpoet') === false
    ) {
      $redirect_url = admin_url('admin.php?page=mailpoet');
    }

    $data = array(
      'settings' => Setting::getAll(),
      'current_user' => wp_get_current_user(),
      'redirect_url' => $redirect_url
    );

    echo $this->renderer->render('update.html', $data);
  }

  function settings() {
    $settings = Setting::getAll();
    $flags = $this->_getFlags();

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
      'segments' => Segment::getPublic()->findArray(),
      'pages' => Pages::getAll(),
      'flags' => $flags,
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
        !(in_array($registration, array(
          'none',
          'blog'
        )));
    } else {
      // check if users can register
      $flags['registration_enabled'] =
        (bool)get_option('users_can_register', false);
    }

    return $flags;
  }

  function subscribers() {
    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('subscribers');
    $data['segments'] = Segment::findArray();

    $data['custom_fields'] = array_map(function($field) {
      $field['params'] = unserialize($field['params']);

      if(!empty($field['params']['values'])) {
        $values = array();

        foreach($field['params']['values'] as $value) {
          $values[$value['value']] = $value['value'];
        }
        $field['params']['values'] = $values;
      }
      return $field;
    }, CustomField::findArray());

    $data['date_formats'] = Block\Date::getDateFormats();
    $data['month_names'] = Block\Date::getMonthNames();

    echo $this->renderer->render('subscribers/subscribers.html', $data);
  }

  function segments() {
    $data = array();
    $data['items_per_page'] = $this->getLimitPerPage('segments');
    echo $this->renderer->render('segments.html', $data);
  }

  function forms() {
    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();

    echo $this->renderer->render('forms.html', $data);
  }

  function newsletters() {
    global $wp_roles;

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('newsletters');
    $data['segments'] = Segment::getSegmentsWithSubscriberCount();
    $data['settings'] = Setting::getAll();
    $data['roles'] = $wp_roles->get_names();
    $data['roles']['mailpoet_all'] = __('In any WordPress role');

    $date_time = new DateTime();
    $data['current_date'] = $date_time->getCurrentDate(DateTime::DEFAULT_DATE_FORMAT);
    $data['current_time'] = $date_time->getCurrentTime();
    $data['schedule_time_of_day'] = $date_time->getTimeInterval(
      '00:00:00',
      '+1 hour',
      24
    );

    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-ui-datepicker');

    echo $this->renderer->render('newsletters.html', $data);
  }

  function newletterEditor() {
    $data = array(
      'shortcodes' => ShortcodesHelper::getShortcodes(),
      'settings' => Setting::getAll(),
      'sub_menu' => 'mailpoet-newsletters'
    );
    wp_enqueue_media();
    wp_enqueue_script('tinymce-wplink', includes_url('js/tinymce/plugins/wplink/plugin.js'));
    wp_enqueue_style('editor', includes_url('css/editor.css'));
    echo $this->renderer->render('newsletter/editor.html', $data);
  }

  function import() {
    $import = new ImportExportFactory('import');
    $data = $import->bootstrap();
    $data['sub_menu'] = 'mailpoet-subscribers';
    echo $this->renderer->render('subscribers/importExport/import.html', $data);
  }

  function export() {
    $export = new ImportExportFactory('export');
    $data = $export->bootstrap();
    $data['sub_menu'] = 'mailpoet-subscribers';
    echo $this->renderer->render('subscribers/importExport/export.html', $data);
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
      'segments' => Segment::getPublic()->findArray(),
      'styles' => FormRenderer::getStyles($form),
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-forms'
    );

    echo $this->renderer->render('form/editor.html', $data);
  }

  function cron() {
    echo $this->renderer->render('cron.html');
  }

  function setPageTitle($title) {
    return sprintf(
      '%s - %s',
      __('MailPoet'),
      $title
    );
  }

  private function getLimitPerPage($model = null) {
    if($model === null) {
      return Listing\Handler::DEFAULT_LIMIT_PER_PAGE;
    }

    $listing_per_page = get_user_meta(
      get_current_user_id(), 'mailpoet_'.$model.'_per_page', true
    );
    return (!empty($listing_per_page))
      ? (int)$listing_per_page
      : Listing\Handler::DEFAULT_LIMIT_PER_PAGE;
  }
}
