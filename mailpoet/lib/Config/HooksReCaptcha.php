<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Captcha\ReCaptchaRenderer;
use MailPoet\Captcha\ReCaptchaValidator;
use MailPoet\Config\Renderer as BasicRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\WP\Functions as WPFunctions;

class HooksReCaptcha {

  const RECAPTCHA_LIB_URL = 'https://www.google.com/recaptcha/api.js';

  /** @var WPFunctions */
  private $wp;

  /** @var BasicRenderer */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var ReCaptchaValidator */
  private $reCaptchaValidator;

  /** @var ReCaptchaRenderer */
  private $reCaptchaRenderer;

  public function __construct(
    WPFunctions $wp,
    BasicRenderer $renderer,
    SettingsController $settings,
    ReCaptchaValidator $reCaptchaValidator,
    ReCaptchaRenderer $reCaptchaRenderer
  ) {
    $this->wp = $wp;
    $this->renderer = $renderer;
    $this->settings = $settings;
    $this->reCaptchaValidator = $reCaptchaValidator;
    $this->reCaptchaRenderer = $reCaptchaRenderer;
  }

  public function isEnabled(): bool {
    // A transient code to enable incremental development of the feature.
    // Later when a setting is introduced, this function will be adjusted.
    if (!in_array(getenv('MP_ENV'), ['development', 'test'])) {
      return false;
    }

    return CaptchaConstants::isReCaptcha(
      $this->settings->get('captcha.type')
    );
  }

  public function enqueueScripts() {
    $this->wp->wpEnqueueScript('mailpoet_recaptcha', self::RECAPTCHA_LIB_URL);

    $this->wp->wpEnqueueStyle(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset('mailpoet-public.css')
    );

    $this->wp->wpEnqueueScript(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('public.js'),
      ['jquery'],
      Env::$version,
      [
        'in_footer' => true,
        'strategy' => 'defer',
      ]
    );

    // necessary for public.js script
    $ajaxFailedErrorMessage = __('An error has happened while performing a request, please try again later.', 'mailpoet');
    $this->wp->wpLocalizeScript('mailpoet_public', 'MailPoetForm', [
      'ajax_url' => $this->wp->adminUrl('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') && is_rtl()),
      'ajax_common_error_message' => esc_js($ajaxFailedErrorMessage),
    ]);
  }

  public function render() {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->reCaptchaRenderer->render();
  }

  public function validate(\WP_Error $errors) {
    try {
      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
      $responseToken = $_POST['g-recaptcha-response'] ?? '';
      $this->reCaptchaValidator->validate($responseToken);
    } catch (\Throwable $e) {
      $errors->add('recaptcha_failed', $e->getMessage());
    }

    return $errors;
  }
}
