<?php

namespace MailPoet\Config;

use Carbon\Carbon;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Helpscout\Beacon;
use MailPoet\Listing;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Router\Endpoints\CronDaemon;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Hosts;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Tasks\Sending;
use MailPoet\Tasks\State;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\DateTime;
use MailPoet\WP\Notice as WPNotice;
use MailPoet\WP\Readme;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Menu {
  const MAIN_PAGE_SLUG = 'mailpoet-newsletters';
  const LAST_ANNOUNCEMENT_DATE = '2019-03-18 10:00:00';

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var Renderer */
  public $renderer;
  public $mp_api_key_valid;
  public $premium_key_valid;

  /** @var AccessControl */
  private $access_control;
  /** @var SettingsController */
  private $settings;
  /** @var WPFunctions */
  private $wp;
  /** @var ServicesChecker */
  private $servicesChecker;

  private $subscribers_over_limit;

  function __construct(
    Renderer $renderer,
    AccessControl $access_control,
    SettingsController $settings,
    WPFunctions $wp,
    WooCommerceHelper $woocommerce_helper,
    ServicesChecker $servicesChecker
  ) {
    $this->renderer = $renderer;
    $this->access_control = $access_control;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->servicesChecker = $servicesChecker;
  }

  function init() {
    $subscribers_feature = new SubscribersFeature();
    $this->subscribers_over_limit = $subscribers_feature->check();
    $this->checkMailPoetAPIKey();
    $this->checkPremiumKey();
    $this->checkFromEmailAuthorization();

    WPFunctions::get()->addAction(
      'admin_menu',
      array(
        $this,
        'setup'
      )
    );
  }

  function setup() {
    if (!$this->access_control->validatePermission(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN)) return;
    if (self::isOnMailPoetAdminPage()) {
      WPFunctions::get()->doAction('mailpoet_conflict_resolver_styles');
      WPFunctions::get()->doAction('mailpoet_conflict_resolver_scripts');

      if ($_REQUEST['page'] === 'mailpoet-newsletter-editor') {
        // Disable WP emojis to not interfere with the newsletter editor emoji handling
        $this->disableWPEmojis();
        WPFunctions::get()->addAction('admin_head', function() {
          $fonts = 'Arvo:400,400i,700,700i'
           . '|Lato:400,400i,700,700i'
           . '|Lora:400,400i,700,700i'
           . '|Merriweather:400,400i,700,700i'
           . '|Merriweather+Sans:400,400i,700,700i'
           . '|Noticia+Text:400,400i,700,700i'
           . '|Open+Sans:400,400i,700,700i'
           . '|Playfair+Display:400,400i,700,700i'
           . '|Roboto:400,400i,700,700i'
           . '|Source+Sans+Pro:400,400i,700,700i'
           . '|Oswald:400,400i,700,700i'
           . '|Raleway:400,400i,700,700i'
           . '|Permanent+Marker:400,400i,700,700i'
           . '|Pacifico:400,400i,700,700i';
          echo '<!--[if !mso]><link href="https://fonts.googleapis.com/css?family=' . $fonts . '" rel="stylesheet"><![endif]-->';
        });
      }
    }

    // Main page
    WPFunctions::get()->addMenuPage(
      'MailPoet',
      'MailPoet',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      self::MAIN_PAGE_SLUG,
      null,
      'none',
      30
    );

    // Emails page
    $newsletters_page = WPFunctions::get()->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Emails', 'mailpoet')),
      WPFunctions::get()->__('Emails', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_EMAILS,
      self::MAIN_PAGE_SLUG,
      array(
        $this,
        'newsletters'
      )
    );

    // add limit per page to screen options
    WPFunctions::get()->addAction('load-' . $newsletters_page, function() {
      WPFunctions::get()->addScreenOption('per_page', array(
        'label' => WPFunctions::get()->x(
          'Number of newsletters per page',
          'newsletters per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_newsletters_per_page'
      ));
    });

    // newsletter editor
    WPFunctions::get()->addSubmenuPage(
      true,
      $this->setPageTitle(__('Newsletter', 'mailpoet')),
      WPFunctions::get()->__('Newsletter Editor', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_EMAILS,
      'mailpoet-newsletter-editor',
      array(
        $this,
        'newletterEditor'
      )
    );

    // Forms page
    $forms_page = WPFunctions::get()->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Forms', 'mailpoet')),
      WPFunctions::get()->__('Forms', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_FORMS,
      'mailpoet-forms',
      array(
        $this,
        'forms'
      )
    );

    // add limit per page to screen options
    WPFunctions::get()->addAction('load-' . $forms_page, function() {
      WPFunctions::get()->addScreenOption('per_page', array(
        'label' => WPFunctions::get()->x(
          'Number of forms per page',
          'forms per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_forms_per_page'
      ));
    });

    // form editor
    WPFunctions::get()->addSubmenuPage(
      true,
      $this->setPageTitle(__('Form Editor', 'mailpoet')),
      WPFunctions::get()->__('Form Editor', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_FORMS,
      'mailpoet-form-editor',
      array(
        $this,
        'formEditor'
      )
    );

    // Subscribers page
    $subscribers_page = WPFunctions::get()->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Subscribers', 'mailpoet')),
      WPFunctions::get()->__('Subscribers', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      'mailpoet-subscribers',
      array(
        $this,
        'subscribers'
      )
    );

    // add limit per page to screen options
    WPFunctions::get()->addAction('load-' . $subscribers_page, function() {
      WPFunctions::get()->addScreenOption('per_page', array(
        'label' => WPFunctions::get()->x(
          'Number of subscribers per page',
          'subscribers per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_subscribers_per_page'
      ));
    });

    // import
    WPFunctions::get()->addSubmenuPage(
      'admin.php?page=mailpoet-subscribers',
      $this->setPageTitle(__('Import', 'mailpoet')),
      WPFunctions::get()->__('Import', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      'mailpoet-import',
      array(
        $this,
        'import'
      )
    );

    // export
    WPFunctions::get()->addSubmenuPage(
      true,
      $this->setPageTitle(__('Export', 'mailpoet')),
      WPFunctions::get()->__('Export', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      'mailpoet-export',
      array(
        $this,
        'export'
      )
    );

    // Segments page
    $segments_page = WPFunctions::get()->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Lists', 'mailpoet')),
      WPFunctions::get()->__('Lists', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SEGMENTS,
      'mailpoet-segments',
      array(
        $this,
        'segments'
      )
    );

    // add limit per page to screen options
    WPFunctions::get()->addAction('load-' . $segments_page, function() {
      WPFunctions::get()->addScreenOption('per_page', array(
        'label' => WPFunctions::get()->x(
          'Number of segments per page',
          'segments per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_segments_per_page'
      ));
    });

    WPFunctions::get()->doAction('mailpoet_menu_after_lists');

    // Settings page
    WPFunctions::get()->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Settings', 'mailpoet')),
      WPFunctions::get()->__('Settings', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SETTINGS,
      'mailpoet-settings',
      array(
        $this,
        'settings'
      )
    );

    // Help page
    WPFunctions::get()->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Help', 'mailpoet')),
      WPFunctions::get()->__('Help', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-help',
      array(
        $this,
        'help'
      )
    );

    // Premium page
    // Only show this page in menu if the Premium plugin is not activated
    WPFunctions::get()->addSubmenuPage(
      License::getLicense() ? true : self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Premium', 'mailpoet')),
      WPFunctions::get()->__('Premium', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-premium',
      array(
        $this,
        'premium'
      )
    );

    // Welcome wizard page
    WPFunctions::get()->addSubmenuPage(
      true,
      $this->setPageTitle(__('Welcome Wizard', 'mailpoet')),
      WPFunctions::get()->__('Welcome Wizard', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-welcome-wizard',
      array(
        $this,
        'welcomeWizard'
      )
    );

    // Update page
    WPFunctions::get()->addSubmenuPage(
      true,
      $this->setPageTitle(__('Update', 'mailpoet')),
      WPFunctions::get()->__('Update', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-update',
      array(
        $this,
        'update'
      )
    );

    // Migration page
    WPFunctions::get()->addSubmenuPage(
      true,
      $this->setPageTitle(__('Migration', 'mailpoet')),
      '',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-migration',
      array(
        $this,
        'migration'
      )
    );
  }

  function disableWPEmojis() {
    WPFunctions::get()->removeAction('admin_print_scripts', 'print_emoji_detection_script');
    WPFunctions::get()->removeAction('admin_print_styles', 'print_emoji_styles');
  }

  function migration() {
    $mp2_migrator = new MP2Migrator();
    $mp2_migrator->init();
    $data = array(
      'log_file_url' => $mp2_migrator->log_file_url,
      'progress_url' => $mp2_migrator->progressbar->url,
    );
    $this->displayPage('mp2migration.html', $data);
  }

  function welcomeWizard() {
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'is_mp2_migration_complete' => (bool)$this->settings->get('mailpoet_migration_complete'),
      'is_woocommerce_active' => $this->woocommerce_helper->isWooCommerceActive(),
      'finish_wizard_url' => WPFunctions::get()->adminUrl('admin.php?page=' . self::MAIN_PAGE_SLUG),
      'sender' => $this->settings->get('sender'),
      'reply_to' => $this->settings->get('reply_to'),
    ];
    $this->displayPage('welcome_wizard.html', $data);
  }

  function update() {
    global $wp;
    $current_url = WPFunctions::get()->homeUrl(add_query_arg($wp->query_string, $wp->request));
    $redirect_url =
      (!empty($_GET['mailpoet_redirect']))
        ? urldecode($_GET['mailpoet_redirect'])
        : WPFunctions::get()->wpGetReferer();

    if (
      $redirect_url === $current_url
      or
      strpos($redirect_url, 'mailpoet') === false
    ) {
      $redirect_url = WPFunctions::get()->adminUrl('admin.php?page=' . self::MAIN_PAGE_SLUG);
    }

    $data = array(
      'settings' => $this->settings->getAll(),
      'current_user' => WPFunctions::get()->wpGetCurrentUser(),
      'redirect_url' => $redirect_url,
      'sub_menu' => self::MAIN_PAGE_SLUG,
    );

    $data['is_new_user'] = true;
    $data['is_old_user'] = false;
    if (!empty($data['settings']['installed_at'])) {
      $installed_at = Carbon::createFromTimestamp(strtotime($data['settings']['installed_at']));
      $current_time = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
      $data['is_new_user'] = $current_time->diffInDays($installed_at) <= 30;
      $data['is_old_user'] = $current_time->diffInMonths($installed_at) >= 6;
      $data['stop_call_for_rating'] = isset($data['settings']['stop_call_for_rating']) ? $data['settings']['stop_call_for_rating'] : false;
    }

    $readme_file = Env::$path . '/readme.txt';
    if (is_readable($readme_file)) {
      $changelog = Readme::parseChangelog(file_get_contents($readme_file), 1);
      if ($changelog) {
        $data['changelog'] = $changelog;
      }
    }

    $this->displayPage('update.html', $data);
  }

  function premium() {
    $data = array(
      'subscriber_count' => Subscriber::getTotalSubscribers(),
      'sub_menu' => self::MAIN_PAGE_SLUG,
      'display_discount' => time() <= strtotime('2018-11-30 23:59:59')
    );

    $this->displayPage('premium.html', $data);
  }


  function settings() {
    $settings = $this->settings->getAll();
    $flags = $this->_getFlags();

    // force MSS key check even if the method isn't active
    $mp_api_key_valid = $this->servicesChecker->isMailPoetAPIKeyValid(false, true);

    $data = array(
      'settings' => $settings,
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'cron_trigger' => CronTrigger::getAvailableMethods(),
      'total_subscribers' => Subscriber::getTotalSubscribers(),
      'premium_plugin_active' => License::getLicense(),
      'premium_key_valid' => !empty($this->premium_key_valid),
      'mss_active' => Bridge::isMPSendingServiceEnabled(),
      'mss_key_valid' => !empty($mp_api_key_valid),
      'members_plugin_active' => WPFunctions::get()->isPluginActive('members/members.php'),
      'pages' => Pages::getAll(),
      'flags' => $flags,
      'current_user' => WPFunctions::get()->wpGetCurrentUser(),
      'linux_cron_path' => dirname(dirname(__DIR__)),
      'is_woocommerce_active' => $this->woocommerce_helper->isWooCommerceActive(),
      'ABSPATH' => ABSPATH,
      'hosts' => array(
        'web' => Hosts::getWebHosts(),
        'smtp' => Hosts::getSMTPHosts()
      )
    );

    $data['is_new_user'] = $this->isNewUser();

    $data = array_merge($data, Installer::getPremiumStatus());

    $this->displayPage('settings.html', $data);
  }


  function help() {
    $tasks_state = new State();
    $system_info_data = Beacon::getData();
    $system_status_data = [
      'cron' => [
        'url' => CronHelper::getCronUrl(CronDaemon::ACTION_PING),
        'isReachable' => CronHelper::pingDaemon(true)
      ],
      'mss' => [
        'enabled' => (Bridge::isMPSendingServiceEnabled()) ?
          ['isReachable' => Bridge::pingBridge()] :
          false
      ],
      'cronStatus' => CronHelper::getDaemon(),
      'queueStatus' => MailerLog::getMailerLog(),
    ];
    $system_status_data['cronStatus']['accessible'] = CronHelper::isDaemonAccessible();
    $system_status_data['queueStatus']['tasksStatusCounts'] = $tasks_state->getCountsPerStatus();
    $system_status_data['queueStatus']['latestTasks'] = $tasks_state->getLatestTasks(Sending::TASK_TYPE);
    $this->displayPage(
      'help.html',
      array(
        'systemInfoData' => $system_info_data,
        'systemStatusData' => $system_status_data
      )
    );
  }

  private function _getFlags() {
    // flags (available features on WP install)
    $flags = array();

    if (is_multisite()) {
      // get multisite registration option
      $registration = WPFunctions::get()->applyFilters(
        'wpmu_registration_enabled',
        WPFunctions::get()->getSiteOption('registration', 'all')
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
    $segments = Segment::getSegmentsWithSubscriberCount($type = false);
    $segments = WPFunctions::get()->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
    usort($segments, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    $data['segments'] = $segments;

    $data['custom_fields'] = array_map(function($field) {
      $field['params'] = unserialize($field['params']);

      if (!empty($field['params']['values'])) {
        $values = array();

        foreach ($field['params']['values'] as $value) {
          $values[$value['value']] = $value['value'];
        }
        $field['params']['values'] = $values;
      }
      return $field;
    }, CustomField::findArray());

    $data['date_formats'] = Block\Date::getDateFormats();
    $data['month_names'] = Block\Date::getMonthNames();

    $data['premium_plugin_active'] = License::getLicense();
    $data['mss_active'] = Bridge::isMPSendingServiceEnabled();

    $this->displayPage('subscribers/subscribers.html', $data);
  }

  function segments() {
    $data = array();
    $data['items_per_page'] = $this->getLimitPerPage('segments');
    $this->displayPage('segments.html', $data);
  }

  function forms() {
    if ($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();

    $data['is_new_user'] = $this->isNewUser();

    $this->displayPage('forms.html', $data);
  }

  function newsletters() {
    if ($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();
    if (isset($this->mp_api_key_valid) && $this->mp_api_key_valid === false) {
      return $this->displayMailPoetAPIKeyInvalidTemplate();
    }

    global $wp_roles;

    $data = array();

    $data['items_per_page'] = $this->getLimitPerPage('newsletters');
    $segments = Segment::getSegmentsWithSubscriberCount($type = false);
    $segments = WPFunctions::get()->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
    usort($segments, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    $data['segments'] = $segments;
    $data['settings'] = $this->settings->getAll();
    $data['current_wp_user'] = WPFunctions::get()->wpGetCurrentUser()->to_array();
    $data['current_wp_user_firstname'] = WPFunctions::get()->wpGetCurrentUser()->user_firstname;
    $data['site_url'] = WPFunctions::get()->siteUrl();
    $data['roles'] = $wp_roles->get_names();
    $data['roles']['mailpoet_all'] = WPFunctions::get()->__('In any WordPress role', 'mailpoet');

    $installedAtDateTime = new \DateTime($data['settings']['installed_at']);
    $data['installed_days_ago'] = (int)$installedAtDateTime->diff(new \DateTime())->format('%a');

    $date_time = new DateTime();
    $data['current_date'] = $date_time->getCurrentDate(DateTime::DEFAULT_DATE_FORMAT);
    $data['current_time'] = $date_time->getCurrentTime();
    $data['schedule_time_of_day'] = $date_time->getTimeInterval(
      '00:00:00',
      '+1 hour',
      24
    );
    $data['mailpoet_main_page'] = WPFunctions::get()->adminUrl('admin.php?page=' . self::MAIN_PAGE_SLUG);
    $data['show_congratulate_after_first_newsletter'] = isset($data['settings']['show_congratulate_after_first_newsletter'])?$data['settings']['show_congratulate_after_first_newsletter']:'false';

    $data['tracking_enabled'] = $this->settings->get('tracking.enabled');
    $data['premium_plugin_active'] = License::getLicense();
    $data['is_woocommerce_active'] = $this->woocommerce_helper->isWooCommerceActive();

    $user_id = $data['current_wp_user']['ID'];
    $data['feature_announcement_has_news'] = empty($data['settings']['last_announcement_seen'][$user_id])
      || $data['settings']['last_announcement_seen'][$user_id] < strtotime(self::LAST_ANNOUNCEMENT_DATE);
    $data['last_announcement_seen'] = isset($data['settings']['last_announcement_seen']) ? $data['settings']['last_announcement_seen'] : false;

    $data['automatic_emails'] = array(
      array(
        'slug' => 'woocommerce',
        'beta' => true,
        'premium' => true,
        'title' => WPFunctions::get()->__('WooCommerce', 'mailpoet'),
        'description' => WPFunctions::get()->__('Automatically send an email when there is a new WooCommerce product, order and some other action takes place.', 'mailpoet'),
        'events' => array(
          array(
            'slug' => 'woocommerce_abandoned_shopping_cart',
            'title' => WPFunctions::get()->__('Abandoned Shopping Cart', 'mailpoet'),
            'description' => WPFunctions::get()->__('Send an email to logged-in visitors who have items in their shopping carts but left your website without checking out. Can convert up to 5% of abandoned carts.', 'mailpoet'),
            'soon' => true,
            'badge' => array(
              'text' => WPFunctions::get()->__('Must-have', 'mailpoet'),
              'style' => 'red'
            )
          ),
          array(
            'slug' => 'woocommerce_big_spender',
            'title' => WPFunctions::get()->__('Big Spender', 'mailpoet'),
            'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who have spent a certain amount to thank them, possibly with a coupon.', 'mailpoet'),
            'soon' => true,
            'badge' => array(
              'text' => WPFunctions::get()->__('Smart to have', 'mailpoet'),
              'style' => 'teal'
            )
          ),
          array(
            'slug' => 'woocommerce_first_purchase',
            'title' => WPFunctions::get()->__('First Purchase', 'mailpoet'),
            'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who make their first purchase.', 'mailpoet'),
            'badge' => array(
              'text' => WPFunctions::get()->__('Must-have', 'mailpoet'),
              'style' => 'red'
            )
          ),
          array(
            'slug' => 'woocommerce_product_purchased_in_category',
            'title' => WPFunctions::get()->__('Purchased In This Category', 'mailpoet'),
            'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who purchase a product from a specific category.', 'mailpoet'),
            'soon' => true
          ),
          array(
            'slug' => 'woocommerce_product_purchased',
            'title' => WPFunctions::get()->__('Purchased This Product', 'mailpoet'),
            'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who purchase a specific product.', 'mailpoet'),
          )
        )
      )
    );

    $data['is_new_user'] = $this->isNewUser();

    WPFunctions::get()->wpEnqueueScript('jquery-ui');
    WPFunctions::get()->wpEnqueueScript('jquery-ui-datepicker');

    $this->displayPage('newsletters.html', $data);
  }

  function newletterEditor() {
    $subscriber = Subscriber::getCurrentWPUser();
    $subscriber_data = $subscriber ? $subscriber->asArray() : [];
    $data = array(
      'shortcodes' => ShortcodesHelper::getShortcodes(),
      'settings' => $this->settings->getAll(),
      'current_wp_user' => array_merge($subscriber_data, WPFunctions::get()->wpGetCurrentUser()->to_array()),
      'sub_menu' => self::MAIN_PAGE_SLUG,
      'mss_active' => Bridge::isMPSendingServiceEnabled()
    );
    WPFunctions::get()->wpEnqueueMedia();
    WPFunctions::get()->wpEnqueueScript('tinymce-wplink', WPFunctions::get()->includesUrl('js/tinymce/plugins/wplink/plugin.js'));
    WPFunctions::get()->wpEnqueueStyle('editor', WPFunctions::get()->includesUrl('css/editor.css'));

    $this->displayPage('newsletter/editor.html', $data);
  }

  function import() {
    $import = new ImportExportFactory(ImportExportFactory::IMPORT_ACTION);
    $data = $import->bootstrap();
    $data = array_merge($data, array(
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-subscribers'
    ));

    $data['is_new_user'] = $this->isNewUser();

    $this->displayPage('subscribers/importExport/import.html', $data);
  }

  function export() {
    $export = new ImportExportFactory(ImportExportFactory::EXPORT_ACTION);
    $data = $export->bootstrap();
    $data['sub_menu'] = 'mailpoet-subscribers';
    $this->displayPage('subscribers/importExport/export.html', $data);
  }

  function formEditor() {
    $id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $form = Form::findOne($id);
    if ($form !== false) {
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
      WPFunctions::get()->__('MailPoet', 'mailpoet'),
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
    if (is_null($screen_id)) {
      if (empty($_REQUEST['page'])) {
        return false;
      }
      $screen_id = $_REQUEST['page'];
    }
    if (!empty($exclude)) {
      foreach ($exclude as $slug) {
        if (stripos($screen_id, $slug) !== false) {
          return false;
        }
      }
    }
    return (stripos($screen_id, 'mailpoet-') !== false);
  }

  /**
   * This error page is used when the initialization is failed
   * to display admin notices only
   */
  static function addErrorPage(AccessControl $access_control) {
    if (!self::isOnMailPoetAdminPage()) {
      return false;
    }
    // Check if page already exists
    if (get_plugin_page_hook($_REQUEST['page'], '')
      || WPFunctions::get()->getPluginPageHook($_REQUEST['page'], self::MAIN_PAGE_SLUG)
    ) {
      return false;
    }
    WPFunctions::get()->addSubmenuPage(
      true,
      'MailPoet',
      'MailPoet',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      $_REQUEST['page'],
      array(
        __CLASS__,
        'errorPageCallback'
      )
    );
  }

  static function errorPageCallback() {
    // Used for displaying admin notices only
  }

  function checkMailPoetAPIKey(ServicesChecker $checker = null) {
    if (self::isOnMailPoetAdminPage()) {
      $show_notices = isset($_REQUEST['page'])
        && stripos($_REQUEST['page'], self::MAIN_PAGE_SLUG) === false;
      $checker = $checker ?: $this->servicesChecker;
      $this->mp_api_key_valid = $checker->isMailPoetAPIKeyValid($show_notices);
    }
  }

  function checkPremiumKey(ServicesChecker $checker = null) {
    $show_notices = isset($_SERVER['SCRIPT_NAME'])
      && stripos($_SERVER['SCRIPT_NAME'], 'plugins.php') !== false;
    $checker = $checker ?: $this->servicesChecker;
    $this->premium_key_valid = $checker->isPremiumKeyValid($show_notices);
  }

  private function checkFromEmailAuthorization() {
    if (self::isOnMailPoetAdminPage() && stripos($_REQUEST['page'], self::MAIN_PAGE_SLUG) === false) {
      $checker = $this->servicesChecker;
      $checker->isFromEmailAuthorized();
    }
  }

  function getLimitPerPage($model = null) {
    if ($model === null) {
      return Listing\Handler::DEFAULT_LIMIT_PER_PAGE;
    }

    $listing_per_page = WPFunctions::get()->getUserMeta(
      WPFunctions::get()->getCurrentUserId(), 'mailpoet_' . $model . '_per_page', true
    );
    return (!empty($listing_per_page))
      ? (int)$listing_per_page
      : Listing\Handler::DEFAULT_LIMIT_PER_PAGE;
  }

  function displayPage($template, $data) {
    try {
      echo $this->renderer->render($template, $data);
    } catch (\Exception $e) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $e->getMessage());
      $notice->displayWPNotice();
    }
  }

  function isNewUser() {
    $installed_at = $this->settings->get('installed_at');
    if (is_null($installed_at)) {
      return true;
    }
    $installed_at = Carbon::createFromTimestamp(strtotime($installed_at));
    $current_time = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    return $current_time->diffInDays($installed_at) <= 30;
  }
}
