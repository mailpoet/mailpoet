<?php declare(strict_types = 1);

namespace MailPoet\AdminPages;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\InvalidStateException;
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
    $this->registerAdminDeps();
    $this->wp->wpEnqueueScript('mailpoet_admin');
  }

  public function setupHomepageDependencies(): void {
    $this->wp->wpEnqueueStyle('mailpoet_homepage', $this->getCssUrl('mailpoet-homepage.css'));
  }

  public function setupNewsletterEditorDependencies(): void {
    $this->enqueueJsEntrypoint('newsletter_editor');
    $this->wp->wpEnqueueStyle('mailpoet_newsletter_editor', $this->getCssUrl('mailpoet-editor.css'));
  }

  public function setupFormEditorDependencies(): void {
    $this->enqueueJsEntrypoint('form_editor');
    $this->wp->wpEnqueueStyle('mailpoet_form_editor', $this->getCssUrl('mailpoet-form-editor.css'));
  }

  public function setupSettingsDependencies(): void {
    $this->enqueueJsEntrypoint('settings');
  }

  public function setupDynamicSegmentsDependencies(): void {
    $this->wp->wpEnqueueStyle('mailpoet_templates', $this->getCssUrl('mailpoet-templates.css'));
  }

  public function setupAutomationListingDependencies(): void {
    $this->enqueueJsEntrypoint('automation');
    $this->wp->wpEnqueueStyle('mailpoet_automation', $this->getCssUrl('mailpoet-automation.css'));
  }

  public function setupAutomationTemplatesDependencies(): void {
    $this->enqueueJsEntrypoint('automation_templates');
    $this->wp->wpEnqueueStyle('mailpoet_automation_templates', $this->getCssUrl('mailpoet-automation-templates.css'));
  }

  public function setupAutomationEditorDependencies(): void {
    $this->enqueueJsEntrypoint('automation_editor', ['wp-date']);
    $this->wp->wpEnqueueStyle('mailpoet_automation_editor', $this->getCssUrl('mailpoet-automation-editor.css'));
  }

  public function setupAutomationAnalyticsDependencies(): void {
    $this->enqueueJsEntrypoint('automation_analytics');
    $this->wp->wpEnqueueStyle('mailpoet_automation_analytics', $this->getCssUrl('mailpoet-automation-analytics.css'));
  }

  private function enqueueJsEntrypoint(string $asset, array $dependencies = []): void {
    $name = 'mailpoet_entrypoint';
    if (isset(\wp_scripts()->registered[$name])) {
      throw new InvalidStateException('JS entrypoint can be enqueued only once');
    }

    $this->registerAdminDeps();
    $this->wp->wpEnqueueScript(
      $name,
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset("$asset.js"),
      array_merge($dependencies, ['mailpoet_admin']),
      Env::$version,
      true
    );
    $this->wp->wpSetScriptTranslations($name, 'mailpoet');

    // append Parsley validation string translations
    $this->wp->wpAddInlineScript($name, $this->renderer->render('parsley-translations.html'));
  }

  private function registerAdminDeps(): void {
    // runtime
    $this->registerFooterScript('mailpoet_runtime', $this->getScriptUrl('runtime.js'));

    // vendor
    $this->registerFooterScript('mailpoet_vendor', $this->getScriptUrl('vendor.js'));

    // commons
    $this->registerFooterScript('mailpoet_admin_commons', $this->getScriptUrl('commons.js'));
    $this->wp->wpSetScriptTranslations('mailpoet_admin_commons', 'mailpoet');

    // mailpoet
    $this->registerFooterScript('mailpoet_mailpoet', $this->getScriptUrl('mailpoet.js'));
    $this->wp->wpSetScriptTranslations('mailpoet_mailpoet', 'mailpoet');

    // admin_vendor
    $this->registerFooterScript(
      'mailpoet_admin_vendor',
      $this->getScriptUrl('admin_vendor.js'),
      [
        'wp-i18n',
        'mailpoet_runtime',
        'mailpoet_vendor',
        'mailpoet_admin_commons',
        'mailpoet_mailpoet',
      ]
    );

    // enqueue "mailpoet_admin_vendor" so the hook fires after it, but before "mailpoet_admin"
    $this->wp->wpEnqueueScript('mailpoet_admin_vendor');
    if ($this->wp->didAction('mailpoet_scripts_admin_before') === 0) {
      $this->wp->doAction('mailpoet_scripts_admin_before');
    }

    // admin
    $this->registerFooterScript(
      'mailpoet_admin',
      $this->getScriptUrl('admin.js'),
      ['mailpoet_admin_vendor']
    );
    $this->wp->wpSetScriptTranslations('mailpoet_admin', 'mailpoet');
  }

  private function getScriptUrl(string $name): string {
    return Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset($name);
  }

  private function getCssUrl(string $name): string {
    return Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset($name);
  }

  private function registerFooterScript(string $handle, string $src, array $deps = []): void {
    $this->wp->wpRegisterScript($handle, $src, $deps, Env::$version, true);
  }
}
