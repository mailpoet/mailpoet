<?php
namespace MailPoet\Config;

use MailPoet\Cron\CronTrigger;
use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Listing;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Settings\Hosts;
use MailPoet\Settings\Pages;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WP\DateTime;
use MailPoet\WP\Notice as WPNotice;
use MailPoet\WP\Readme;

if(!defined('ABSPATH')) exit;

class Menu {
  function __construct($renderer, $assets_url) {
    $this->renderer = $renderer;
    $this->assets_url = $assets_url;
    $subscribers_feature = new SubscribersFeature();
    $this->subscribers_over_limit = $subscribers_feature->check();
    $this->checkMailPoetAPIKey();
    $this->checkPremiumKey();
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
    if(self::isOnMailPoetAdminPage()) {
      do_action('mailpoet_conflict_resolver_styles');
      do_action('mailpoet_conflict_resolver_scripts');
    }

    $main_page_slug = 'mailpoet-newsletters';

    add_menu_page(
      'MailPoet',
      'MailPoet',
      Env::$required_permission,
      $main_page_slug,
      null,
      $this->assets_url . '/img/menu_icon.png',
      30
    );

    $newsletters_page = add_submenu_page(
      $main_page_slug,
      $this->setPageTitle(__('Emails', 'mailpoet')),
      __('Emails', 'mailpoet'),
      Env::$required_permission,
      $main_page_slug,
      array(
        $this,
        'newsletters'
      )
    );

    // add limit per page to screen options
    add_action('load-' . $newsletters_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of newsletters per page',
          'newsletters per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_newsletters_per_page'
      ));
    });

    $forms_page = add_submenu_page(
      $main_page_slug,
      $this->setPageTitle(__('Forms', 'mailpoet')),
      __('Forms', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-forms',
      array(
        $this,
        'forms'
      )
    );
    // add limit per page to screen options
    add_action('load-' . $forms_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of forms per page',
          'forms per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_forms_per_page'
      ));
    });

    $subscribers_page = add_submenu_page(
      $main_page_slug,
      $this->setPageTitle(__('Subscribers', 'mailpoet')),
      __('Subscribers', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-subscribers',
      array(
        $this,
        'subscribers'
      )
    );
    // add limit per page to screen options
    add_action('load-' . $subscribers_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of subscribers per page',
          'subscribers per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_subscribers_per_page'
      ));
    });

    $segments_page = add_submenu_page(
      $main_page_slug,
      $this->setPageTitle(__('Lists', 'mailpoet')),
      __('Lists', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-segments',
      array(
        $this,
        'segments'
      )
    );

    // add limit per page to screen options
    add_action('load-' . $segments_page, function() {
      add_screen_option('per_page', array(
        'label' => _x(
          'Number of segments per page',
          'segments per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_segments_per_page'
      ));
    });

    add_submenu_page(
      $main_page_slug,
      $this->setPageTitle(__('Settings', 'mailpoet')),
      __('Settings', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-settings',
      array(
        $this,
        'settings'
      )
    );

    // Only show this page in menu if the Premium plugin is not activated
    add_submenu_page(
      License::getLicense() ? true : $main_page_slug,
      $this->setPageTitle(__('Premium', 'mailpoet')),
      __('Premium', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-premium',
      array(
        $this,
        'premium'
      )
    );

    add_submenu_page(
      'admin.php?page=mailpoet-subscribers',
      $this->setPageTitle(__('Import', 'mailpoet')),
      __('Import', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-import',
      array(
        $this,
        'import'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Export', 'mailpoet')),
      __('Export', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-export',
      array(
        $this,
        'export'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Welcome', 'mailpoet')),
      __('Welcome', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-welcome',
      array(
        $this,
        'welcome'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Update', 'mailpoet')),
      __('Update', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-update',
      array(
        $this,
        'update'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Form Editor', 'mailpoet')),
      __('Form Editor', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-form-editor',
      array(
        $this,
        'formEditor'
      )
    );

    add_submenu_page(
      true,
      $this->setPageTitle(__('Newsletter', 'mailpoet')),
      __('Newsletter Editor', 'mailpoet'),
      Env::$required_permission,
      'mailpoet-newsletter-editor',
      array(
        $this,
        'newletterEditor'
      )
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
    $this->displayPage('welcome.html', $data);
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

    $readme_file = Env::$path . '/readme.txt';
    if(is_readable($readme_file)) {
      $changelog = Readme::parseChangelog(file_get_contents($readme_file), 2);
      if($changelog) {
        $data['changelog'] = $changelog;
      }
    }

    $this->displayPage('update.html', $data);
  }

  function premium() {
    $data = array(
      'subscriber_count' => Subscriber::getTotalSubscribers(),
      'sub_menu' => 'mailpoet-newsletters'
    );

    $this->displayPage('premium.html', $data);
  }


  function settings() {
    if($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();

    $settings = Setting::getAll();
    $flags = $this->_getFlags();

    // force MSS key check even if the method isn't active
    $checker = new ServicesChecker();
    $mp_api_key_valid = $checker->isMailPoetAPIKeyValid(false, true);

    $data = array(
      'settings' => $settings,
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'cron_trigger' => CronTrigger::getAvailableMethods(),
      'total_subscribers' => Subscriber::getTotalSubscribers(),
      'premium_plugin_active' => License::getLicense(),
      'premium_key_valid' => !empty($this->premium_key_valid),
      'mss_key_valid' => !empty($mp_api_key_valid),
      'pages' => Pages::getAll(),
      'flags' => $flags,
      'current_user' => wp_get_current_user(),
      'hosts' => array(
        'web' => Hosts::getWebHosts(),
        'smtp' => Hosts::getSMTPHosts()
      )
    );

    $data = array_merge($data, Installer::getPremiumStatus());

    $this->displayPage('settings.html', $data);
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
    $data['segments'] = Segment::getSegmentsWithSubscriberCount($type = false);

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

    $data['premium_plugin_active'] = License::getLicense();

    $this->displayPage('subscribers/subscribers.html', $data);
  }

  function segments() {
    if($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();

    $data = array();
    $data['items_per_page'] = $this->getLimitPerPage('segments');
    $this->displayPage('segments.html', $data);
  }

  function forms() {
    if($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();

    $this->displayPage('forms.html', $data);
  }

  function newsletters() {
    if($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();
    if(isset($this->mp_api_key_valid) && $this->mp_api_key_valid === false) {
      return $this->displayMailPoetAPIKeyInvalidTemplate();
    }

    global $wp_roles;

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('newsletters');
    $data['segments'] = Segment::getSegmentsWithSubscriberCount($type = false);
    $data['settings'] = Setting::getAll();
    $data['roles'] = $wp_roles->get_names();
    $data['roles']['mailpoet_all'] = __('In any WordPress role', 'mailpoet');

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

    $this->displayPage('newsletters.html', $data);
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

    $this->displayPage('newsletter/editor.html', $data);
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
    $this->displayPage('subscribers/importExport/import.html', $data);
  }

  function export() {
    $export = new ImportExportFactory('export');
    $data = $export->bootstrap();
    $data['sub_menu'] = 'mailpoet-subscribers';
    $this->displayPage('subscribers/importExport/export.html', $data);
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
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'styles' => FormRenderer::getStyles($form),
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-forms'
    );

    $this->displayPage('form/editor.html', $data);
  }

  function setPageTitle($title) {
    return sprintf(
      '%s - %s',
      __('MailPoet', 'mailpoet'),
      $title
    );
  }

  function displaySubscriberLimitExceededTemplate() {
    $this->displayPage('limit.html', array(
      'limit' => SubscribersFeature::SUBSCRIBERS_LIMIT
    ));
    exit;
  }

  function displayMailPoetAPIKeyInvalidTemplate() {
    $this->displayPage('invalidkey.html', array(
      'subscriber_count' => Subscriber::getTotalSubscribers()
    ));
    exit;
  }

  static function isOnMailPoetAdminPage(array $exclude = null, $screen_id = null) {
    if(is_null($screen_id)) {
      if(empty($_REQUEST['page'])) {
        return false;
      }
      $screen_id = $_REQUEST['page'];
    }
    if(!empty($exclude)) {
      foreach($exclude as $slug) {
        if(stripos($screen_id, $slug) !== false) {
          return false;
        }
      }
    }
    return (stripos($screen_id, 'mailpoet-') !== false);
  }

  function checkMailPoetAPIKey(ServicesChecker $checker = null) {
    if(self::isOnMailPoetAdminPage()) {
      $show_notices = isset($_REQUEST['page'])
        && stripos($_REQUEST['page'], 'mailpoet-newsletters') === false;
      $checker = $checker ?: new ServicesChecker();
      $this->mp_api_key_valid = $checker->isMailPoetAPIKeyValid($show_notices);
    }
  }

  function checkPremiumKey(ServicesChecker $checker = null) {
    if(self::isOnMailPoetAdminPage()) {
      $show_notices = isset($_REQUEST['page'])
        && stripos($_REQUEST['page'], 'mailpoet-newsletters') === false;
      $checker = $checker ?: new ServicesChecker();
      $this->premium_key_valid = $checker->isPremiumKeyValid($show_notices);
    }
  }

  private function getLimitPerPage($model = null) {
    if($model === null) {
      return Listing\Handler::DEFAULT_LIMIT_PER_PAGE;
    }

    $listing_per_page = get_user_meta(
      get_current_user_id(), 'mailpoet_' . $model . '_per_page', true
    );
    return (!empty($listing_per_page))
      ? (int)$listing_per_page
      : Listing\Handler::DEFAULT_LIMIT_PER_PAGE;
  }

  private function displayPage($template, $data) {
    try {
      echo $this->renderer->render($template, $data);
    } catch(\Exception $e) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $e->getMessage());
      $notice->displayWPNotice();
    }
  }
}
