<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer as BasicRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class TurnstileHooks {

  const TURNSTILE_LIB_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

  /** @var WPFunctions */
  private $wp;

  /** @var BasicRenderer */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var TurnstileValidator */
  private $turnstileValidator;

  /** @var TurnstileRenderer */
  private $turnstileRenderer;

  public function __construct(
    WPFunctions $wp,
    BasicRenderer $renderer,
    SettingsController $settings,
    TurnstileValidator $turnstileValidator,
    TurnstileRenderer $turnstileRenderer
  ) {
    $this->wp = $wp;
    $this->renderer = $renderer;
    $this->settings = $settings;
    $this->turnstileValidator = $turnstileValidator;
    $this->turnstileRenderer = $turnstileRenderer;
  }

  public function isEnabled(): bool {
    try {
      if (!$this->settings->get(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false)) {
        return false;
      }

      return CaptchaConstants::isTurnstile(
        $this->settings->get('captcha.type')
      );
    } catch (\Throwable $e) {
      return false;
    }
  }

  public function enqueueScripts() {
    $this->wp->wpEnqueueScript(
      'mailpoet_turnstile',
      self::TURNSTILE_LIB_URL,
      [],
      false,
      [
        'in_footer' => true,
        'strategy' => 'defer',
      ]
    );

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

    $ajaxFailedErrorMessage = __('An error has happened while performing a request, please try again later.', 'mailpoet');
    $this->wp->wpLocalizeScript('mailpoet_public', 'MailPoetForm', [
      'ajax_url' => $this->wp->adminUrl('admin-ajax.php'),
      'is_rtl' => $this->wp->isRtl(),
      'ajax_common_error_message' => $this->wp->escJs($ajaxFailedErrorMessage),
      'collect_subscriber_timezones' => $this->settings->isSettingEnabled('collect_subscriber_timezones.enabled'),
    ]);
  }

  public function render() {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->turnstileRenderer->render();
  }

  public function validate(\WP_Error $errors) {
    try {
      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
      $responseToken = $_POST['cf-turnstile-response'] ?? '';
      $this->turnstileValidator->validate(is_string($responseToken) ? $responseToken : '');
    } catch (\Throwable $e) {
      $errors->add('turnstile_failed', $e->getMessage());
    }

    return $errors;
  }
}
