<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\Installer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class PremiumFeaturesAvailableNoticeTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var PremiumFeaturesAvailableNotice */
  private $notice;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->notice = $this->diContainer->get(PremiumFeaturesAvailableNotice::class);
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

    $message = $this->notice->display()->getMessage();

    verify($message)->stringContainsString('<form method="post" action="' . Installer::PREMIUM_PLUGIN_DOWNLOAD_URL . '"');
    verify($message)->stringContainsString('name="api_key" value="' . $key . '"');
    verify($message)->stringNotContainsString('mailpoet-premium/' . $key . '/');
  }
}
