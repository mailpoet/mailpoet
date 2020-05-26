<?php

namespace MailPoet\Form;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer as BasicRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\WP\Functions as WPFunctions;

class AssetsController {
  /** @var WPFunctions */
  private $wp;

  /** @var BasicRenderer */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  const RECAPTCHA_API_URL = 'https://www.google.com/recaptcha/api.js?onload=reCaptchaCallback&render=explicit';

  public function __construct(WPFunctions $wp, BasicRenderer $renderer, SettingsController $settings) {
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
    $this->wp->wpPrintScripts('jquery');
    $this->wp->wpPrintScripts('mailpoet_vendor');
    $this->wp->wpPrintScripts('mailpoet_public');
    echo '<script src="' . self::RECAPTCHA_API_URL . '" async defer></script>';
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
    $this->wp->wpEnqueueStyle(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset('mailpoet-public.css')
    );

    $this->wp->wpEnqueueScript(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('public.js'),
      ['jquery'],
      Env::$version,
      true
    );

    $captcha = $this->settings->get('captcha');
    if (!empty($captcha['type']) && $captcha['type'] === Captcha::TYPE_RECAPTCHA) {
      $this->wp->wpEnqueueScript(
        'mailpoet_recaptcha',
        self::RECAPTCHA_API_URL,
        ['mailpoet_public']
      );
    }

    $this->wp->wpLocalizeScript('mailpoet_public', 'MailPoetForm', [
      'ajax_url' => $this->wp->adminUrl('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false),
    ]);

    $ajaxFailedErrorMessage = $this->wp->__('An error has happened while performing a request, please try again later.');

    $inlineScript = <<<EOL
function initMailpoetTranslation() {
  if (typeof MailPoet !== 'undefined') {
    MailPoet.I18n.add('ajaxFailedErrorMessage', '%s')
  } else {
    setTimeout(initMailpoetTranslation, 250);
  }
}
setTimeout(initMailpoetTranslation, 250);
EOL;
    $this->wp->wpAddInlineScript(
      'mailpoet_public',
      sprintf($inlineScript, $ajaxFailedErrorMessage),
      'after'
    );
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
