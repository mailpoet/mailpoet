<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Settings\Hosts;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\Util\Installation;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class Settings {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settings;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var Captcha */
  private $captcha;

  /** @var Installation */
  private $installation;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settings,
    WooCommerceHelper $woocommerceHelper,
    WPFunctions $wp,
    ServicesChecker $servicesChecker,
    Installation $installation,
    Captcha $captcha,
    SegmentSubscribersRepository $segmentSubscribersRepository
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settings = $settings;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wp = $wp;
    $this->servicesChecker = $servicesChecker;
    $this->installation = $installation;
    $this->captcha = $captcha;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
  }

  public function render() {
    $settings = $this->settings->getAll();

    $premiumKeyValid = $this->servicesChecker->isPremiumKeyValid(false);
    // force MSS key check even if the method isn't active
    $mpApiKeyValid = $this->servicesChecker->isMailPoetAPIKeyValid(false, true);
    $installer = new Installer(Installer::PREMIUM_PLUGIN_SLUG);
    $pluginInformation = $installer->retrievePluginInformation();

    $data = [
      'settings' => $settings,
      'segments' => $this->segmentSubscribersRepository->getSimpleSegmentListWithSubscribersCounts(SegmentEntity::TYPE_DEFAULT),
      'premium_key_valid' => !empty($premiumKeyValid),
      'mss_key_valid' => !empty($mpApiKeyValid),
      'pages' => Pages::getAll(),
      'current_user' => $this->wp->wpGetCurrentUser(),
      'is_woocommerce_active' => $this->woocommerceHelper->isWooCommerceActive(),
      'is_members_plugin_active' => $this->wp->isPluginActive('members/members.php'),
      'premium_plugin_download_url' => $pluginInformation->download_link ?? null, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'hosts' => [
        'web' => Hosts::getWebHosts(),
        'smtp' => Hosts::getSMTPHosts(),
      ],
      'paths' => [
        'root' => ABSPATH,
        'plugin' => dirname(dirname(dirname(__DIR__))),
      ],
      'built_in_captcha_supported' => $this->captcha->isSupported(),
    ];

    $data['is_new_user'] = $this->installation->isNewInstallation();

    $data = array_merge($data, Installer::getPremiumStatus());

    if (isset($_GET['enable-customizer-notice'])) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $this->wp->_x(
        'You need to have WooCommerce active to access the MailPoet email customizer for WooCommerce.',
        'Notice in Settings when WooCommerce is not enabled'
      ), 'mailpoet');
      $notice->displayWPNotice();
    }
    $this->pageRenderer->displayPage('settings.html', $data);
  }
}
