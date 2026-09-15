<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class CaptchaDisabledNotice {

  const OPTION_NAME = 'dismissed-captcha-disabled-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days

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

  public function init(bool $shouldDisplay): ?Notice {
    if (
      $shouldDisplay
      && !$this->wp->getTransient(self::OPTION_NAME)
      && $this->shouldDisplay()
    ) {
      return $this->display();
    }
    return null;
  }

  public function disable(): void {
    $this->wp->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

  public function display(): Notice {
    $settingsUrl = $this->wp->adminUrl('admin.php?page=mailpoet-settings#/advanced');
    $message = Helpers::replaceLinkTags(
      __('Turn on CAPTCHA to stop bots from signing up through your MailPoet forms. Bot sign-ups fill your list with fake addresses and hurt your deliverability. If we detect them on your site, we may need to restrict sending until you enable protection. You can ignore this if you are already handling bot protection elsewhere, like a WAF or Cloudflare. [link]Enable CAPTCHA[/link]', 'mailpoet'),
      $settingsUrl
    );

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';
    return Notice::displayInfo($message, $extraClasses, self::OPTION_NAME);
  }

  private function shouldDisplay(): bool {
    $captchaDisabled = CaptchaConstants::isDisabled($this->settings->get(CaptchaConstants::TYPE_SETTING_NAME));
    return $captchaDisabled && $this->bridge->isMailpoetSendingServiceEnabled();
  }
}
