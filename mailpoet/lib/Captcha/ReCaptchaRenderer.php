<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class ReCaptchaRenderer {

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function render(): string {
    $captchaSettings = $this->settings->get('captcha');
    $isInvisible = $captchaSettings['type'] === CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE;

    if ($isInvisible) {
      $siteKey = $this->wp->escAttr($captchaSettings['recaptcha_invisible_site_token']);
      $html = '<div class="g-recaptcha" data-size="invisible" data-callback="onInvisibleReCaptchaSubmit" data-sitekey="' . $siteKey . '"></div>';
    } else {
      $siteKey = $this->wp->escAttr($captchaSettings['recaptcha_site_token']);
      $html = '<div class="g-recaptcha" data-sitekey="' . $siteKey . '"></div>';
    }

    return $html;
  }
}
