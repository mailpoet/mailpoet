<?php
namespace MailPoet\Config;

use MailPoet\Cron\CronTrigger;
use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Settings\Hosts;
use MailPoet\Settings\Pages;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
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

  function checkSubscribersLimit() {
    $subscribers_count = Subscriber::getTotalSubscribers();
    if($subscribers_count > Env::$subscribers_limit) {
      echo $this->renderer->render('limit.html', array(
        'limit' => Env::$subscribers_limit
      ));
      exit;
    }
  }

  function setup() {
    $main_page_slug = 'mailpoet-newsletters';

    add_menu_page(
      'MailPoet',
      'MailPoet',
      'manage_options',
      $main_page_slug,
      null,
      $this->assets_url . '/img/menu_icon.png',
      30
    );

    $newsletters_page = add_submenu_page(
      $main_page_slug,
      $this->setPageTitle(__('Newsletters', Env::$plugin_name)),
      __('Newsletters', Env::$plugin_name),
      'manage_options',
      $main_page_slug,
      array($this, 'newsletters')
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
      $main_page_slug,
      $this->setPageTitle(__('Forms', Env::$plugin_name)),
      __('Forms', Env::$plugin_name),
      'manage_options',
      'mailpoet-forms',
      array($this, 'forms')
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
      $main_page_slug,
      $this->setPageTitle(__('Subscribers', Env::$plugin_name)),
      __('Subscribers', Env::$plugin_name),
      'manage_options',
      'mailpoet-subscribers',
      array($this, 'subscribers')
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
      $main_page_slug,
      $this->setPageTitle(__('Lists', Env::$plugin_name)),
      __('Lists', Env::$plugin_name),
      'manage_options',
      'mailpoet-segments',
      array($this, 'segments')
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
      $main_page_slug,
      $this->setPageTitle( __('Settings', Env::$plugin_name)),
      __('Settings', Env::$plugin_name),
      'manage_options',
      'mailpoet-settings',
      array($this, 'settings')
    );
    add_submenu_page(
      'admin.php?page=mailpoet-subscribers',
      $this->setPageTitle( __('Import', Env::$plugin_name)),
      __('Import', Env::$plugin_name),
      'manage_options',
      'mailpoet-import',
      array($this, 'import')
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Export', Env::$plugin_name)),
      __('Export', Env::$plugin_name),
      'manage_options',
      'mailpoet-export',
      array($this, 'export')
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Welcome', Env::$plugin_name)),
      __('Welcome', Env::$plugin_name),
      'manage_options',
      'mailpoet-welcome',
      array($this, 'welcome')
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Update', Env::$plugin_name)),
      __('Update', Env::$plugin_name),
      'manage_options',
      'mailpoet-update',
      array($this, 'update')
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Form Editor', Env::$plugin_name)),
      __('Form Editor', Env::$plugin_name),
      'manage_options',
      'mailpoet-form-editor',
      array($this, 'formEditor')
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Newsletter', Env::$plugin_name)),
      __('Newsletter Editor', Env::$plugin_name),
      'manage_options',
      'mailpoet-newsletter-editor',
      array($this, 'newletterEditor')
    );
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
      $redirect_url = admin_url('admin.php?page=mailpoet-newsletters');
    }

    $data = array(
      'settings' => Setting::getAll(),
      'current_user' => wp_get_current_user(),
      'redirect_url' => $redirect_url,
      'sub_menu' => 'mailpoet-newsletters'
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
      $redirect_url = admin_url('admin.php?page=mailpoet-newsletters');
    }

    $data = array(
      'settings' => Setting::getAll(),
      'current_user' => wp_get_current_user(),
      'redirect_url' => $redirect_url,
      'sub_menu' => 'mailpoet-newsletters'
    );

    echo $this->renderer->render('update.html', $data);
  }

  function settings() {
    $this->checkSubscribersLimit();

    $settings = Setting::getAll();
    $flags = $this->_getFlags();

    $data = array(
      'settings' => $settings,
      'segments' => Segment::getPublic()->findArray(),
      'cron_trigger' => CronTrigger::getAvailableMethods(),
      'pages' => Pages::getAll(),
      'flags' => $flags,
      'current_user' => wp_get_current_user(),
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
    $this->checkSubscribersLimit();

    $data = array();
    $data['items_per_page'] = $this->getLimitPerPage('segments');
    echo $this->renderer->render('segments.html', $data);
  }

  function forms() {
    $this->checkSubscribersLimit();

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();

    echo $this->renderer->render('forms.html', $data);
  }

  function newsletters() {
    $this->checkSubscribersLimit();

    global $wp_roles;

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('newsletters');
    $data['segments'] = Segment::getSegmentsWithSubscriberCount($type = false);
    $data['settings'] = Setting::getAll();
    $data['roles'] = $wp_roles->get_names();
    $data['roles']['mailpoet_all'] = __('In any WordPress role', Env::$plugin_name);

    $date_time = new DateTime();
    $data['current_date'] = $date_time->getCurrentDate(DateTime::DEFAULT_DATE_FORMAT);
    $data['current_time'] = $date_time->getCurrentTime();
    $data['schedule_time_of_day'] = $date_time->getTimeInterval(
      '00:00:00',
      '+1 hour',
      24
    );

    $data['tracking_enabled'] = Setting::getValue('tracking.enabled');

    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-ui-datepicker');

    echo $this->renderer->render('newsletters.html', $data);
  }

  function newletterEditor() {
    $data = array(
      'shortcodes' => ShortcodesHelper::getShortcodes(),
      'settings' => Setting::getAll(),
      'current_wp_user' => Subscriber::getCurrentWPUser(),
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
    $data = array_merge($data, array(
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-subscribers'
    ));
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

  function setPageTitle($title) {
    return sprintf(
      '%s - %s',
      __('MailPoet', Env::$plugin_name),
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
