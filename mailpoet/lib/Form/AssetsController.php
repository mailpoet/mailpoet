<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Form;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer as BasicRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
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
      echo '<script src="' . esc_attr(self::RECAPTCHA_API_URL) . '" async defer></script>';
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

    $this->wp->wpEnqueueScript(
      'mailpoet_public',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('public.js'),
      ['jquery'],
      Env::$version,
      true
    );

    $this->wp->wpLocalizeScript('mailpoet_public', 'MailPoetForm', [
      'ajax_url' => $this->wp->adminUrl('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false),
    ]);

    $ajaxFailedErrorMessage = __('An error has happened while performing a request, please try again later.', 'mailpoet');

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
      sprintf($inlineScript, esc_js($ajaxFailedErrorMessage)),
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

  public function setupAutomationListingDependencies(): void {
    $this->wp->wpEnqueueScript(
      'automation',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('automation.js'),
      [],
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations('automation', 'mailpoet');
  }

  public function setupAutomationEditorDependencies(): void {
    $this->wp->wpEnqueueScript(
      'automation_editor',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('automation_editor.js'),
      ['wp-date'],
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations('automation_editor', 'mailpoet');
  }

  public function setupAutomationAnalyticsDependencies(): void {
    $this->wp->wpEnqueueScript(
      'automation_analytics',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('automation_analytics.js'),
      [],
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations('automation_analytics', 'mailpoet');

    $this->wp->wpEnqueueStyle(
      'automation_analytics',
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset('mailpoet-automation-analytics.css')
    );
  }

  public function setupAutomationTemplatesDependencies(): void {
    $this->wp->wpEnqueueScript(
      'automation_templates',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('automation_templates.js'),
      [],
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations('automation_templates', 'mailpoet');
  }

  public function setupNewsletterEditorDependencies(): void {

    $this->wp->wpRegisterScript(
      'newsletter_editor',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('newsletter_editor.js'),
      [],
      Env::$version,
      true
    );

    $this->wp->wpPrintScripts('wp-i18n');
    $this->wp->wpSetScriptTranslations('newsletter_editor', 'mailpoet');

    /**
     * The js file needs to be added immediately since the mailpoet_newsletters_editor_initialize hook is dispatched in template files
     * Update and remove this line in MAILPOET-4930
     */
    \wp_scripts()->do_item('newsletter_editor');
  }

  public function setupAdminPagesDependencies(): void {
    $this->wp->wpPrintScripts('wp-i18n');
    $this->addAdminCommons();
    $this->wp->wpEnqueueScript(
      'mailpoet_admin_pages',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('admin.js'),
      [],
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations('mailpoet_admin_pages', 'mailpoet');
  }

  private function addAdminCommons(): void {
    $this->wp->wpRegisterScript(
      'mailpoet_admin_commons',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('commons.js'),
      [],
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations('mailpoet_admin_commons', 'mailpoet');


    /**
     * The js file needs to be added immediately since the mailpoet_newsletters_editor_initialize hook is dispatched in template files
     * Update and remove this line in MAILPOET-4930
     */
    \wp_scripts()->do_item('mailpoet_admin_commons');
  }
}
