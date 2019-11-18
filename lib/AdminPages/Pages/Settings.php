<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronTrigger;
use MailPoet\Features\FeaturesController;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Hosts;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\Util\Installation;
use MailPoet\Util\License\License;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class Settings {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var SettingsController */
  private $settings;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var WPFunctions */
  private $wp;

  /** @var ServicesChecker */
  private $services_checker;

  /** @var Captcha */
  private $captcha;

  /** @var FeaturesController */
  private $features_controller;

  /** @var Installation */
  private $installation;

  function __construct(
    PageRenderer $page_renderer,
    SettingsController $settings,
    WooCommerceHelper $woocommerce_helper,
    WPFunctions $wp,
    ServicesChecker $services_checker,
    Installation $installation,
    Captcha $captcha,
    FeaturesController $features_controller
  ) {
    $this->page_renderer = $page_renderer;
    $this->settings = $settings;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->wp = $wp;
    $this->services_checker = $services_checker;
    $this->installation = $installation;
    $this->captcha = $captcha;
    $this->features_controller = $features_controller;
  }

  function render() {
    $settings = $this->settings->getAll();
    $flags = $this->getFlags();

    $premium_key_valid = $this->services_checker->isPremiumKeyValid(false);
    // force MSS key check even if the method isn't active
    $mp_api_key_valid = $this->services_checker->isMailPoetAPIKeyValid(false, true);

    $data = [
      'settings' => $settings,
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'cron_trigger' => CronTrigger::METHODS,
      'total_subscribers' => Subscriber::getTotalSubscribers(),
      'premium_plugin_active' => License::getLicense(),
      'premium_key_valid' => !empty($premium_key_valid),
      'mss_active' => Bridge::isMPSendingServiceEnabled(),
      'mss_key_valid' => !empty($mp_api_key_valid),
      'members_plugin_active' => $this->wp->isPluginActive('members/members.php'),
      'pages' => Pages::getAll(),
      'flags' => $flags,
      'current_user' => $this->wp->wpGetCurrentUser(),
      'linux_cron_path' => dirname(dirname(dirname(__DIR__))),
      'is_woocommerce_active' => $this->woocommerce_helper->isWooCommerceActive(),
      'ABSPATH' => ABSPATH,
      'hosts' => [
        'web' => Hosts::getWebHosts(),
        'smtp' => Hosts::getSMTPHosts(),
      ],
      'built_in_captcha_supported' => $this->captcha->isSupported(),
      'display_woocommerce_editor' => $this->features_controller->isSupported(
        FeaturesController::WC_TRANSACTIONAL_EMAILS_CUSTOMIZER
      ),
    ];

    $data['is_new_user'] = $this->installation->isNewInstallation();

    $data = array_merge($data, Installer::getPremiumStatus());

    if (isset($_GET['enable-customizer-notice'])) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $this->wp->_x(
        'You need to enable MailPoet email customizer for WooCommerce if you want to access to the customizer.',
        'Notice in Settings when WooCommerce customizer is not enabled'
      ), 'mailpoet');
      $notice->displayWPNotice();
    }
    $this->page_renderer->displayPage('settings.html', $data);
  }

  private function getFlags() {
    // flags (available features on WP install)
    $flags = [];
    if ($this->wp->isMultisite()) {
      // get multisite registration option
      $registration = $this->wp->applyFilters(
        'wpmu_registration_enabled',
        $this->wp->getSiteOption('registration', 'all')
      );

      // check if users can register
      $flags['registration_enabled'] =
        !(in_array($registration, [
          'none',
          'blog',
        ]));
    } else {
      // check if users can register
      $flags['registration_enabled'] =
        (bool)$this->wp->getOption('users_can_register', false);
    }

    return $flags;
  }
}
