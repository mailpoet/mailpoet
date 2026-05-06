<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class TurnstileRenderer {

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

  public function render(?string $responseFieldName = null): string {
    $captchaSettings = $this->settings->get('captcha');
    $siteKey = $this->wp->escAttr($captchaSettings['turnstile_site_token'] ?? '');
    $responseFieldNameAttribute = $responseFieldName !== null
      ? ' data-response-field-name="' . $this->wp->escAttr($responseFieldName) . '"'
      : '';

    return '<div class="cf-turnstile" data-sitekey="' . $siteKey . '"' . $responseFieldNameAttribute . '></div>';
  }
}
