<?php declare(strict_types = 1);

namespace MailPoet\AdminPages;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class AssetsController {
  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
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
