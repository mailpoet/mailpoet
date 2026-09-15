<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class CaptchaDisabledNotice {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    Bridge $bridge,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->wp = $wp;
  }

  public function render(): void {
    if (!$this->shouldDisplay()) {
      return;
    }

    $settingsUrl = $this->wp->adminUrl('admin.php?page=mailpoet-settings#/advanced');
    $message = Helpers::replaceLinkTags(
      __('CAPTCHA helps protect your forms from spam and abuse. If bot activity is detected, form sending may be restricted until protection is enabled. If other effective anti-spam measures are in place, this notice can be ignored. [link]Enable CAPTCHA[/link]', 'mailpoet'),
      $settingsUrl
    );

    $notice = new WPNotice(WPNotice::TYPE_INFO, $message);
    $notice->displayWPNotice();
  }

  private function shouldDisplay(): bool {
    $captchaDisabled = CaptchaConstants::isDisabled($this->settings->get(CaptchaConstants::TYPE_SETTING_NAME));
    return $captchaDisabled && $this->bridge->isMailpoetSendingServiceEnabled();
  }
}
