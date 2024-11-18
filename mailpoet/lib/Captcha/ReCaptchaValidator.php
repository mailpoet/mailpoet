<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class ReCaptchaValidator {

  private const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

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
    $captchaSettings = $this->settings->get('captcha');
    if (empty($responseToken)) {
      throw new \Exception(__('Please check the CAPTCHA.', 'mailpoet'));
    }

    $secretToken = $captchaSettings['type'] === CaptchaConstants::TYPE_RECAPTCHA
      ? $captchaSettings['recaptcha_secret_token']
      : $captchaSettings['recaptcha_invisible_secret_token'];

    $response = $this->wp->wpRemotePost(self::ENDPOINT, [
      'body' => [
        'secret' => $secretToken,
        'response' => $responseToken,
      ],
    ]);

    if ($this->wp->isWpError($response)) {
      throw new \Exception(__('Error while validating the CAPTCHA.', 'mailpoet'));
    }

    /** @var \stdClass $response */
    $response = json_decode($this->wp->wpRemoteRetrieveBody($response));
    if ($response === null) { // invalid JSON
      throw new \Exception(__('Error while validating the CAPTCHA.', 'mailpoet'));
    } else if (empty($response->success)) { // missing or false
      throw new \Exception(__('Invalid CAPTCHA. Try again.', 'mailpoet'));
    }
  }
}
