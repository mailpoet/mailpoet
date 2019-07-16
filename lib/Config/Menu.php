<?php

namespace MailPoet\Config;

use MailPoet\AdminPages\Pages\ExperimentalFeatures;
use MailPoet\AdminPages\Pages\FormEditor;
use MailPoet\AdminPages\Pages\Forms;
use MailPoet\AdminPages\Pages\Help;
use MailPoet\AdminPages\Pages\MP2Migration;
use MailPoet\AdminPages\Pages\NewsletterEditor;
use MailPoet\AdminPages\Pages\Newsletters;
use MailPoet\AdminPages\Pages\Premium;
use MailPoet\AdminPages\Pages\RevenueTrackingPermission;
use MailPoet\AdminPages\Pages\Segments;
use MailPoet\AdminPages\Pages\Settings;
use MailPoet\AdminPages\Pages\Subscribers;
use MailPoet\AdminPages\Pages\SubscribersAPIKeyInvalid;
use MailPoet\AdminPages\Pages\SubscribersExport;
use MailPoet\AdminPages\Pages\SubscribersImport;
use MailPoet\AdminPages\Pages\SubscribersLimitExceeded;
use MailPoet\AdminPages\Pages\Update;
use MailPoet\AdminPages\Pages\WelcomeWizard;
use MailPoet\AdminPages\Pages\WooCommerceListImport;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\License\License;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Menu {
  const MAIN_PAGE_SLUG = 'mailpoet-newsletters';

  public $mp_api_key_valid;
  public $premium_key_valid;

  /** @var AccessControl */
  private $access_control;

  /** @var WPFunctions */
  private $wp;

  /** @var ServicesChecker */
  private $services_checker;

  /** @var ContainerWrapper */
  private $container;

  private $subscribers_over_limit;

  function __construct(
    AccessControl $access_control,
    WPFunctions $wp,
    ServicesChecker $services_checker,
    ContainerWrapper $container
  ) {
    $this->access_control = $access_control;
    $this->wp = $wp;
    $this->services_checker = $services_checker;
    $this->container = $container;
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
    $this->container->get(MP2Migration::class)->render();
  }

  function welcomeWizard() {
    $this->container->get(WelcomeWizard::class)->render();
  }

  function wooCommerceListImport() {
    $this->container->get(WooCommerceListImport::class)->render();
  }

  function revenueTrackingPermission() {
    $this->container->get(RevenueTrackingPermission::class)->render();
  }

  function update() {
    $this->container->get(Update::class)->render();
  }

  function premium() {
    $this->container->get(Premium::class)->render();
  }

  function settings() {
    $this->container->get(Settings::class)->render();
  }

  function help() {
    $this->container->get(Help::class)->render();
  }

  function experimentalFeatures() {
    $this->container->get(ExperimentalFeatures::class)->render();
  }

  function subscribers() {
    $this->container->get(Subscribers::class)->render();
  }

  function segments() {
    $this->container->get(Segments::class)->render();
  }

  function forms() {
    if ($this->subscribers_over_limit) return $this->displaySubscriberLimitExceeded();
    $this->container->get(Forms::class)->render();
  }

  function newsletters() {
    if ($this->subscribers_over_limit) return $this->displaySubscriberLimitExceeded();
    if (isset($this->mp_api_key_valid) && $this->mp_api_key_valid === false) {
      return $this->displayMailPoetAPIKeyInvalid();
    }
    $this->container->get(Newsletters::class)->render();
  }

  function newletterEditor() {
    $this->container->get(NewsletterEditor::class)->render();
  }

  function import() {
    $this->container->get(SubscribersImport::class)->render();
  }

  function export() {
    $this->container->get(SubscribersExport::class)->render();
  }

  function formEditor() {
    $this->container->get(FormEditor::class)->render();
  }

  private function displaySubscriberLimitExceeded() {
    $this->container->get(SubscribersLimitExceeded::class)->render();
    exit;
  }

  private function displayMailPoetAPIKeyInvalid() {
    $this->container->get(SubscribersAPIKeyInvalid::class)->render();
    exit;
  }

  function setPageTitle($title) {
    return sprintf(
      '%s - %s',
      $this->wp->__('MailPoet', 'mailpoet'),
      $title
    );
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
      $checker = $checker ?: $this->services_checker;
      $this->mp_api_key_valid = $checker->isMailPoetAPIKeyValid($show_notices);
    }
  }

  function checkPremiumKey(ServicesChecker $checker = null) {
    $show_notices = isset($_SERVER['SCRIPT_NAME'])
      && stripos($_SERVER['SCRIPT_NAME'], 'plugins.php') !== false;
    $checker = $checker ?: $this->services_checker;
    $this->premium_key_valid = $checker->isPremiumKeyValid($show_notices);
  }
}
