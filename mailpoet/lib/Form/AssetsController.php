<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Config\Env;
use MailPoet\Config\Renderer as BasicRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class AssetsController {
  /** @var WPFunctions */
  private $wp;

  /** @var BasicRenderer */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  const RECAPTCHA_API_URL = 'https://www.google.com/recaptcha/api.js?render=explicit';

  public function __construct(
    WPFunctions $wp,
    BasicRenderer $renderer,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->renderer = $renderer;
    $this->settings = $settings;
  }

  /**
   * Returns assets scripts tags as string
   * @return string
   */
  public function printScripts() {
    ob_start();
    $captcha = $this->settings->get('captcha');
    if (!empty($captcha['type']) && CaptchaConstants::isReCaptcha($captcha['type'])) {
      echo '<script src="' . esc_url(self::RECAPTCHA_API_URL) . '" async defer></script>';
    }

    $this->wp->wpPrintScripts('jquery');
    $this->wp->wpPrintScripts('mailpoet_vendor');
    $this->wp->wpPrintScripts('mailpoet_public');

    $scripts = ob_get_contents();
    ob_end_clean();
    if ($scripts === false) {
      return '';
    }
    return $scripts;
  }

  public function setupFormPreviewDependencies() {
    $this->setupFrontEndDependencies();
    $this->wp->wpEnqueueScript(
      'mailpoet_form_preview',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('form_preview.js'),
      ['jquery'],
      Env::$version,
      true
    );
  }

  public function setupFrontEndDependencies() {
    $captcha = $this->settings->get('captcha');
    if (!empty($captcha['type']) && CaptchaConstants::isRecaptcha($captcha['type'])) {
      $this->wp->wpEnqueueScript(
        'mailpoet_recaptcha',
        self::RECAPTCHA_API_URL
      );
    }

    $this->wp->wpEnqueueStyle(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset('mailpoet-public.css')
    );

    $enqueuePlacementParams = [
      'in_footer' => true,
      'strategy' => 'defer',
    ];

    $this->wp->wpEnqueueScript(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('public.js'),
      ['jquery'],
      Env::$version,
      $enqueuePlacementParams
    );

    $ajaxFailedErrorMessage = __('An error has happened while performing a request, please try again later.', 'mailpoet');
    $this->wp->wpLocalizeScript('mailpoet_public', 'MailPoetForm', [
      'ajax_url' => $this->wp->adminUrl('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false),
      'ajax_common_error_message' => esc_js($ajaxFailedErrorMessage),
      'captcha_input_label' => esc_js(__('Type in the characters you see in the picture above:', 'mailpoet')),
      'captcha_reload_title' => esc_js(__('Reload CAPTCHA', 'mailpoet')),
      'captcha_audio_title' => esc_js(__('Play CAPTCHA', 'mailpoet')),
      'assets_url' => Env::$assetsUrl,
    ]);
  }

  public function setupAdminWidgetPageDependencies() {
    $this->wp->wpEnqueueScript(
      'mailpoet_vendor',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('vendor.js'),
      [],
      Env::$version,
      true
    );

    $this->wp->wpEnqueueScript(
      'mailpoet_admin',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('mailpoet.js'),
      [],
      Env::$version,
      true
    );
  }
}
