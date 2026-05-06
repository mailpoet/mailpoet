<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class TurnstileValidator {

  private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  /**
   * @throws \Exception response token is missing or invalid.
   */
  public function validate(string $responseToken) {
    if (empty($responseToken)) {
      throw new \Exception(__('Please check the CAPTCHA.', 'mailpoet'));
    }

    $captchaSettings = $this->settings->get('captcha');
    $body = [
      'secret' => $captchaSettings['turnstile_secret_token'] ?? '',
      'response' => $responseToken,
    ];
    $remoteIp = Helpers::getIP();
    if (is_string($remoteIp) && $remoteIp !== '') {
      $body['remoteip'] = $remoteIp;
    }
    $response = $this->wp->wpRemotePost(self::ENDPOINT, [
      'body' => $body,
      'timeout' => 5,
    ]);

    if ($this->wp->isWpError($response)) {
      throw new \Exception(__('Error while validating the CAPTCHA.', 'mailpoet'));
    }

    /** @var \stdClass $response */
    $response = json_decode($this->wp->wpRemoteRetrieveBody($response));
    if ($response === null) {
      throw new \Exception(__('Error while validating the CAPTCHA.', 'mailpoet'));
    } else if (empty($response->success)) {
      throw new \Exception(__('Invalid CAPTCHA. Try again.', 'mailpoet'));
    }
  }
}
