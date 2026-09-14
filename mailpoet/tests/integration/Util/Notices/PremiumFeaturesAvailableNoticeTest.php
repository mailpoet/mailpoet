<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class PremiumFeaturesAvailableNoticeTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var PremiumFeaturesAvailableNotice */
  private $notice;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->notice = new PremiumFeaturesAvailableNotice(
      $this->diContainer->get(SubscribersFeature::class),
      new ServicesChecker(),
      new WPFunctions()
    );
  }

  public function _after() {
    parent::_after();
    $this->settings->delete(Bridge::PREMIUM_KEY_SETTING_NAME);
    $this->settings->delete('premium.premium_key_state');
  }

  public function testDownloadLinkIsAPostFormWithTheKeyOutOfTheUrl(): void {
    $key = 'some-premium-key';
    $this->settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, $key);
    $this->settings->set('premium.premium_key_state.state', Bridge::KEY_VALID);

    $notice = $this->notice->display();
    ob_start();
    $notice->displayWPNotice();
    $output = ob_get_clean();

    $expectedUrl = (new Installer(Installer::PREMIUM_PLUGIN_PATH))->buildDownloadUrl();
    verify($output)->stringContainsString('<form method="post" action="' . esc_url($expectedUrl) . '"');
    verify($output)->stringContainsString('name="api_key" value="' . esc_attr($key) . '"');
    verify($output)->stringNotContainsString('mailpoet-premium/' . $key . '/');
  }
}
