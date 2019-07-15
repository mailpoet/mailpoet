<?php

namespace MailPoet\Config;

use Carbon\Carbon;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\AdminPages\Pages\Newsletters;
use MailPoet\AdminPages\Pages\Settings;
use MailPoet\AdminPages\Pages\WelcomeWizard;
use MailPoet\Cron\CronHelper;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeaturesController;
use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Helpscout\Beacon;
use MailPoet\Listing;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\ModelValidator;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Router\Endpoints\CronDaemon;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Tasks\Sending;
use MailPoet\Tasks\State;
use MailPoet\Util\Installation;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WP\Readme;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Menu {
  const MAIN_PAGE_SLUG = 'mailpoet-newsletters';

  /** @var Renderer */
  public $renderer;
  public $mp_api_key_valid;
  public $premium_key_valid;

  /** @var AccessControl */
  private $access_control;
  /** @var SettingsController */
  private $settings;

  /** @var FeaturesController */
  private $features_controller;

  /** @var UserFlagsController */
  private $user_flags;
  /** @var WPFunctions */
  private $wp;
  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var PageRenderer */
  private $page_renderer;

  /** @var Listing\PageLimit */
  private $listing_page_limit;

  /** @var Installation */
  private $installation;

  /** @var ContainerWrapper */
  private $container;

  private $subscribers_over_limit;

  function __construct(
    AccessControl $access_control,
    SettingsController $settings,
    FeaturesController $featuresController,
    WPFunctions $wp,
    ServicesChecker $servicesChecker,
    UserFlagsController $user_flags,
    PageRenderer $page_renderer,
    Listing\PageLimit $listing_page_limit,
    Installation $installation,
    ContainerWrapper $containerWrapper
  ) {
    $this->access_control = $access_control;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->features_controller = $featuresController;
    $this->servicesChecker = $servicesChecker;
    $this->user_flags = $user_flags;
    $this->page_renderer = $page_renderer;
    $this->listing_page_limit = $listing_page_limit;
    $this->installation = $installation;
    $this->container = $containerWrapper;
  }

  function init() {
    $subscribers_feature = new SubscribersFeature();
    $this->subscribers_over_limit = $subscribers_feature->check();
    $this->checkMailPoetAPIKey();
    $this->checkPremiumKey();

    $this->wp->addAction(
      'admin_menu',
      [
        $this,
        'setup',
      ]
    );
  }

  function setup() {
    if (!$this->access_control->validatePermission(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN)) return;
    if (self::isOnMailPoetAdminPage()) {
      $this->wp->doAction('mailpoet_conflict_resolver_styles');
      $this->wp->doAction('mailpoet_conflict_resolver_scripts');

      if ($_REQUEST['page'] === 'mailpoet-newsletter-editor') {
        // Disable WP emojis to not interfere with the newsletter editor emoji handling
        $this->disableWPEmojis();
        $this->wp->addAction('admin_head', function() {
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
    $this->wp->addMenuPage(
      'MailPoet',
      'MailPoet',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      self::MAIN_PAGE_SLUG,
      null,
      'none',
      30
    );

    // Emails page
    $newsletters_page = $this->wp->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Emails', 'mailpoet')),
      $this->wp->__('Emails', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_EMAILS,
      self::MAIN_PAGE_SLUG,
      [
        $this,
        'newsletters',
      ]
    );

    // add limit per page to screen options
    $this->wp->addAction('load-' . $newsletters_page, function() {
      $this->wp->addScreenOption('per_page', [
        'label' => $this->wp->_x(
          'Number of newsletters per page',
          'newsletters per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_newsletters_per_page',
      ]);
    });

    // newsletter editor
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle(__('Newsletter', 'mailpoet')),
      $this->wp->__('Newsletter Editor', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_EMAILS,
      'mailpoet-newsletter-editor',
      [
        $this,
        'newletterEditor',
      ]
    );

    // Forms page
    $forms_page = $this->wp->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Forms', 'mailpoet')),
      $this->wp->__('Forms', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_FORMS,
      'mailpoet-forms',
      [
        $this,
        'forms',
      ]
    );

    // add limit per page to screen options
    $this->wp->addAction('load-' . $forms_page, function() {
      $this->wp->addScreenOption('per_page', [
        'label' => $this->wp->_x(
          'Number of forms per page',
          'forms per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_forms_per_page',
      ]);
    });

    // form editor
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle(__('Form Editor', 'mailpoet')),
      $this->wp->__('Form Editor', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_FORMS,
      'mailpoet-form-editor',
      [
        $this,
        'formEditor',
      ]
    );

    // Subscribers page
    $subscribers_page = $this->wp->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Subscribers', 'mailpoet')),
      $this->wp->__('Subscribers', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      'mailpoet-subscribers',
      [
        $this,
        'subscribers',
      ]
    );

    // add limit per page to screen options
    $this->wp->addAction('load-' . $subscribers_page, function() {
      $this->wp->addScreenOption('per_page', [
        'label' => $this->wp->_x(
          'Number of subscribers per page',
          'subscribers per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_subscribers_per_page',
      ]);
    });

    // import
    $this->wp->addSubmenuPage(
      'admin.php?page=mailpoet-subscribers',
      $this->setPageTitle(__('Import', 'mailpoet')),
      $this->wp->__('Import', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      'mailpoet-import',
      [
        $this,
        'import',
      ]
    );

    // export
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle(__('Export', 'mailpoet')),
      $this->wp->__('Export', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
      'mailpoet-export',
      [
        $this,
        'export',
      ]
    );

    // Segments page
    $segments_page = $this->wp->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Lists', 'mailpoet')),
      $this->wp->__('Lists', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SEGMENTS,
      'mailpoet-segments',
      [
        $this,
        'segments',
      ]
    );

    // add limit per page to screen options
    $this->wp->addAction('load-' . $segments_page, function() {
      $this->wp->addScreenOption('per_page', [
        'label' => $this->wp->_x(
          'Number of segments per page',
          'segments per page (screen options)',
          'mailpoet'
        ),
        'option' => 'mailpoet_segments_per_page',
      ]);
    });

    $this->wp->doAction('mailpoet_menu_after_lists');

    // Settings page
    $this->wp->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Settings', 'mailpoet')),
      $this->wp->__('Settings', 'mailpoet'),
      AccessControl::PERMISSION_MANAGE_SETTINGS,
      'mailpoet-settings',
      [
        $this,
        'settings',
      ]
    );

    // Help page
    $this->wp->addSubmenuPage(
      self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Help', 'mailpoet')),
      $this->wp->__('Help', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-help',
      [
        $this,
        'help',
      ]
    );

    // Premium page
    // Only show this page in menu if the Premium plugin is not activated
    $this->wp->addSubmenuPage(
      License::getLicense() ? true : self::MAIN_PAGE_SLUG,
      $this->setPageTitle(__('Premium', 'mailpoet')),
      $this->wp->__('Premium', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-premium',
      [
        $this,
        'premium',
      ]
    );

    // Welcome wizard page
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle(__('Welcome Wizard', 'mailpoet')),
      $this->wp->__('Welcome Wizard', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-welcome-wizard',
      [
        $this,
        'welcomeWizard',
      ]
    );

    // WooCommerce List Import
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle($this->wp->__('WooCommerce List Import', 'mailpoet')),
      $this->wp->__('WooCommerce List Import', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-woocommerce-list-import',
      [
        $this,
        'wooCommerceListImport',
      ]
    );

    // WooCommerce List Import
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle($this->wp->__('Track WooCommerce revenues with cookies', 'mailpoet')),
      $this->wp->__('Track WooCommerce revenues with cookies', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-revenue-tracking-permission',
      [
        $this,
        'revenueTrackingPermission',
      ]
    );

    // Update page
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle(__('Update', 'mailpoet')),
      $this->wp->__('Update', 'mailpoet'),
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-update',
      [
        $this,
        'update',
      ]
    );

    // Migration page
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle(__('Migration', 'mailpoet')),
      '',
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN,
      'mailpoet-migration',
      [
        $this,
        'migration',
      ]
    );

    // Settings page
    $this->wp->addSubmenuPage(
      true,
      $this->setPageTitle('Experimental Features'),
      '',
      AccessControl::PERMISSION_MANAGE_FEATURES,
      'mailpoet-experimental',
      [$this, 'experimentalFeatures']
    );
  }

  function disableWPEmojis() {
    $this->wp->removeAction('admin_print_scripts', 'print_emoji_detection_script');
    $this->wp->removeAction('admin_print_styles', 'print_emoji_styles');
  }

  function migration() {
    $mp2_migrator = new MP2Migrator();
    $mp2_migrator->init();
    $data = [
      'log_file_url' => $mp2_migrator->log_file_url,
      'progress_url' => $mp2_migrator->progressbar->url,
    ];
    $this->page_renderer->displayPage('mp2migration.html', $data);
  }

  function welcomeWizard() {
    $this->container->get(WelcomeWizard::class)->render();
  }

  function wooCommerceListImport() {
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . self::MAIN_PAGE_SLUG),
    ];
    $this->page_renderer->displayPage('woocommerce_list_import.html', $data);
  }

  function revenueTrackingPermission() {
    if (!$this->features_controller->isSupported(FeaturesController::FEATURE_DISPLAY_WOOCOMMERCE_REVENUES)) {
      return;
    }
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . self::MAIN_PAGE_SLUG),
    ];
    $this->page_renderer->displayPage('revenue_tracking_permission.html', $data);
  }

  function update() {
    global $wp;
    $current_url = $this->wp->homeUrl(add_query_arg($wp->query_string, $wp->request));
    $redirect_url =
      (!empty($_GET['mailpoet_redirect']))
        ? urldecode($_GET['mailpoet_redirect'])
        : $this->wp->wpGetReferer();

    if (
      $redirect_url === $current_url
      or
      strpos($redirect_url, 'mailpoet') === false
    ) {
      $redirect_url = $this->wp->adminUrl('admin.php?page=' . self::MAIN_PAGE_SLUG);
    }

    $data = [
      'settings' => $this->settings->getAll(),
      'current_user' => $this->wp->wpGetCurrentUser(),
      'redirect_url' => $redirect_url,
      'sub_menu' => self::MAIN_PAGE_SLUG,
    ];

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

    $this->page_renderer->displayPage('update.html', $data);
  }

  function premium() {
    $data = [
      'subscriber_count' => Subscriber::getTotalSubscribers(),
      'sub_menu' => self::MAIN_PAGE_SLUG,
      'display_discount' => time() <= strtotime('2018-11-30 23:59:59'),
    ];

    $this->page_renderer->displayPage('premium.html', $data);
  }

  function settings() {
    $this->container->get(Settings::class)->render();
  }

  function help() {
    $tasks_state = new State();
    $system_info_data = Beacon::getData();
    $system_status_data = [
      'cron' => [
        'url' => CronHelper::getCronUrl(CronDaemon::ACTION_PING),
        'isReachable' => CronHelper::pingDaemon(true),
      ],
      'mss' => [
        'enabled' => (Bridge::isMPSendingServiceEnabled()) ?
          ['isReachable' => Bridge::pingBridge()] :
          false,
      ],
      'cronStatus' => CronHelper::getDaemon(),
      'queueStatus' => MailerLog::getMailerLog(),
    ];
    $system_status_data['cronStatus']['accessible'] = CronHelper::isDaemonAccessible();
    $system_status_data['queueStatus']['tasksStatusCounts'] = $tasks_state->getCountsPerStatus();
    $system_status_data['queueStatus']['latestTasks'] = $tasks_state->getLatestTasks(Sending::TASK_TYPE);
    $this->page_renderer->displayPage(
      'help.html',
      [
        'systemInfoData' => $system_info_data,
        'systemStatusData' => $system_status_data,
      ]
    );
  }

  function experimentalFeatures() {
    $this->page_renderer->displayPage('experimental-features.html', []);
  }

  function subscribers() {
    $data = [];

    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('subscribers');
    $segments = Segment::getSegmentsWithSubscriberCount($type = false);
    $segments = $this->wp->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
    usort($segments, function ($a, $b) {
      return strcasecmp($a["name"], $b["name"]);
    });
    $data['segments'] = $segments;

    $data['custom_fields'] = array_map(function($field) {
      $field['params'] = unserialize($field['params']);

      if (!empty($field['params']['values'])) {
        $values = [];

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

    $this->page_renderer->displayPage('subscribers/subscribers.html', $data);
  }

  function segments() {
    $data = [];
    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('segments');
    $this->page_renderer->displayPage('segments.html', $data);
  }

  function forms() {
    if ($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();

    $data = [];

    $data['items_per_page'] = $this->listing_page_limit->getLimitPerPage('forms');
    $data['segments'] = Segment::findArray();

    $data['is_new_user'] = $this->installation->isNewInstallation();

    $this->page_renderer->displayPage('forms.html', $data);
  }

  function newsletters() {
    if ($this->subscribers_over_limit) return $this->displaySubscriberLimitExceededTemplate();
    if (isset($this->mp_api_key_valid) && $this->mp_api_key_valid === false) {
      return $this->displayMailPoetAPIKeyInvalidTemplate();
    }
    $this->container->get(Newsletters::class)->render();
  }

  function newletterEditor() {
    $subscriber = Subscriber::getCurrentWPUser();
    $subscriber_data = $subscriber ? $subscriber->asArray() : [];
    $data = [
      'shortcodes' => ShortcodesHelper::getShortcodes(),
      'settings' => $this->settings->getAll(),
      'editor_tutorial_seen' => $this->user_flags->get('editor_tutorial_seen'),
      'current_wp_user' => array_merge($subscriber_data, $this->wp->wpGetCurrentUser()->to_array()),
      'sub_menu' => self::MAIN_PAGE_SLUG,
      'mss_active' => Bridge::isMPSendingServiceEnabled(),
    ];
    $this->wp->wpEnqueueMedia();
    $this->wp->wpEnqueueScript('tinymce-wplink', $this->wp->includesUrl('js/tinymce/plugins/wplink/plugin.js'));
    $this->wp->wpEnqueueStyle('editor', $this->wp->includesUrl('css/editor.css'));

    $this->page_renderer->displayPage('newsletter/editor.html', $data);
  }

  function import() {
    $import = new ImportExportFactory(ImportExportFactory::IMPORT_ACTION);
    $data = $import->bootstrap();
    $data = array_merge($data, [
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-subscribers',
      'role_based_emails' => json_encode(ModelValidator::ROLE_EMAILS),
    ]);

    $data['is_new_user'] = $this->installation->isNewInstallation();

    $this->page_renderer->displayPage('subscribers/importExport/import.html', $data);
  }

  function export() {
    $export = new ImportExportFactory(ImportExportFactory::EXPORT_ACTION);
    $data = $export->bootstrap();
    $data['sub_menu'] = 'mailpoet-subscribers';
    $this->page_renderer->displayPage('subscribers/importExport/export.html', $data);
  }

  function formEditor() {
    $id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form = $form->asArray();
    }

    $data = [
      'form' => $form,
      'pages' => Pages::getAll(),
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'styles' => FormRenderer::getStyles($form),
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-forms',
    ];

    $this->page_renderer->displayPage('form/editor.html', $data);
  }

  function setPageTitle($title) {
    return sprintf(
      '%s - %s',
      $this->wp->__('MailPoet', 'mailpoet'),
      $title
    );
  }

  function displaySubscriberLimitExceededTemplate() {
    $this->page_renderer->displayPage('limit.html', [
      'limit' => SubscribersFeature::SUBSCRIBERS_LIMIT,
    ]);
    exit;
  }

  function displayMailPoetAPIKeyInvalidTemplate() {
    $this->page_renderer->displayPage('invalidkey.html', [
      'subscriber_count' => Subscriber::getTotalSubscribers(),
    ]);
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
      [
        __CLASS__,
        'errorPageCallback',
      ]
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
}
