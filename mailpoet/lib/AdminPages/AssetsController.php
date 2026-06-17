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

  /** @var bool */
  private $skipAdminPagesDependencies = false;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
  }

  public function setupAdminPagesDependencies(): void {
    if ($this->skipAdminPagesDependencies) {
      return;
    }
    $this->registerAdminDeps();
    $this->wp->wpEnqueueScript('mailpoet_admin');
  }

  public function setupHomepageDependencies(): void {
    $this->wp->wpEnqueueStyle('mailpoet_homepage', $this->getCssUrl('mailpoet-homepage.css'));
  }

  public function setupNewsletterEditorDependencies(): void {
    $this->enqueueJsEntrypoint('newsletter_editor', ['underscore']);
    $this->wp->wpEnqueueStyle('mailpoet_newsletter_editor', $this->getCssUrl('mailpoet-editor.css'));
  }

  public function setupFormEditorDependencies(): void {
    $this->skipAdminPagesDependencies = true;
    $this->setupFormEditorLocalizationDependency();
    $this->enqueueJsEntrypoint('form_editor', ['mailpoet_mailpoet', 'underscore'], false);
    $this->wp->wpEnqueueStyle('mailpoet_form_editor', $this->getCssUrl('mailpoet-form-editor.css'));
  }

  public function setupSettingsDependencies(): void {
    $this->enqueueJsEntrypoint('settings');
  }

  public function setupTagsDependencies(): void {
    $this->enqueueJsEntrypoint('tags');
    $this->setupDataViewsDependencies();
    $this->wp->wpEnqueueStyle('mailpoet_tags', $this->getCssUrl('mailpoet-tags.css'));
  }

  public function setupCustomFieldsDependencies(): void {
    $this->enqueueJsEntrypoint('custom_fields');
    $this->setupDataViewsDependencies();
    $this->wp->wpEnqueueStyle('mailpoet_custom_fields', $this->getCssUrl('mailpoet-custom-fields.css'));
  }

  /**
   * Enqueue the WordPress component + DataViews styles required by listings
   * that render `@wordpress/dataviews`. Listings already shipped via the
   * shared admin JS bundle (e.g. Forms) only need the styles enqueued; their
   * JS lives in `webpack-admin-index`.
   *
   * `wp-components` is registered by WordPress core. `wp-dataviews` is
   * package-shipped, so we bundle its build-style into `mailpoet-dataviews.css`.
   */
  public function setupDataViewsDependencies(): void {
    $this->wp->wpEnqueueStyle('wp-components');
    $this->wp->wpEnqueueStyle('mailpoet_dataviews', $this->getCssUrl('mailpoet-dataviews.css'));
  }

  public function setupDynamicSegmentsDependencies(): void {
    $this->wp->wpEnqueueStyle('mailpoet_templates', $this->getCssUrl('mailpoet-templates.css'));
    $this->wp->wpEnqueueStyle('mailpoet_dynamic_segments', $this->getCssUrl('mailpoet-dynamic-segments.css'));
  }

  public function setupAutomationListingDependencies(): void {
    $this->enqueueJsEntrypoint('automation');
    $this->setupDataViewsDependencies();
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

  public function setupAutomationPreviewEmbedDependencies(): void {
    $this->enqueueJsEntrypoint('automation_preview_embed');
    $this->wp->wpEnqueueStyle('mailpoet_automation_templates', $this->getCssUrl('mailpoet-automation-templates.css'));
  }

  public function setupAutomationFlowEmbedDependencies(): void {
    $this->enqueueJsEntrypoint('automation_flow_embed');
    $this->wp->wpEnqueueStyle('mailpoet_automation_analytics', $this->getCssUrl('mailpoet-automation-analytics.css'));
  }

  private function enqueueJsEntrypoint(string $asset, array $dependencies = [], bool $withAdminDeps = true): void {
    if ($withAdminDeps) {
      $this->registerAdminDeps();
    }

    $assetData = $this->getScriptAssetData($asset);
    $dependencies = array_values(array_unique(array_merge(
      $dependencies,
      $assetData['dependencies'],
      $withAdminDeps ? ['mailpoet_admin'] : []
    )));

    $name = "mailpoet_$asset";
    $this->wp->wpEnqueueScript(
      $name,
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset("$asset.js"),
      $dependencies,
      $assetData['version'],
      true
    );
    $this->wp->wpSetScriptTranslations($name, 'mailpoet');

    // Ensure Lodash doesn't override Underscore from WordPress on "window._" global.
    // Checking for "_.at" detects Lodash (the function doesn't exist in Underscore).
    $noConflict = 'if (window._ && window._.at && window._.noConflict) window._.noConflict();';
    if ($withAdminDeps) {
      $this->wp->wpAddInlineScript('mailpoet_admin_commons', $noConflict);
      $this->wp->wpAddInlineScript('mailpoet_mailpoet', $noConflict);
      $this->wp->wpAddInlineScript('mailpoet_admin_vendor', $noConflict);
      $this->wp->wpAddInlineScript('mailpoet_admin', $noConflict);
    }
    $this->wp->wpAddInlineScript($name, $noConflict);
  }

  /**
   * @return array{dependencies: string[], version: string}
   */
  private function getScriptAssetData(string $asset): array {
    $assetPath = Env::$assetsPath . '/dist/js/' . $asset . '.asset.php';
    $assetData = file_exists($assetPath) ? require $assetPath : [];

    return [
      'dependencies' => $assetData['dependencies'] ?? [],
      'version' => $assetData['version'] ?? Env::$version,
    ];
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

    // append Parsley validation string translations
    $this->wp->wpAddInlineScript('mailpoet_admin_vendor', $this->renderer->render('parsley-translations.html'));

    // enqueue "mailpoet_admin_vendor" so the hook fires after it, but before "mailpoet_admin"
    $this->wp->wpEnqueueScript('mailpoet_admin_vendor');
    if ($this->wp->didAction('mailpoet_scripts_admin_before') === 0) {
      $this->wp->doAction('mailpoet_scripts_admin_before');
    }

    // admin
    $this->registerFooterScript(
      'mailpoet_admin',
      $this->getScriptUrl('admin.js'),
      ['mailpoet_admin_vendor', 'wp-preferences-persistence']
    );
    $this->wp->wpLocalizeScript('mailpoet_admin', 'mailpoet_preferences_data', [
      'currentUserId' => $this->wp->getCurrentUserId(),
      'preloadedData' => $this->getPersistedPreferences(),
    ]);
    $this->wp->wpSetScriptTranslations('mailpoet_admin', 'mailpoet');
  }

  private function setupFormEditorLocalizationDependency(): void {
    $this->wp->wpRegisterScript('mailpoet_mailpoet', false, [], Env::$version, true);
    $this->wp->wpAddInlineScript(
      'mailpoet_mailpoet',
      <<<'JAVASCRIPT'
window.mailpoet_i18n = window.mailpoet_i18n || {};
window.MailPoet = window.MailPoet || {};
window.MailPoet.I18n = window.MailPoet.I18n || {
  add: function(key, value) {
    window.mailpoet_i18n[key] = value;
  },
  t: function(key) {
    return window.mailpoet_i18n[key] || 'TRANSLATION "%1$s" NOT FOUND'.replace('%1$s', key);
  },
  all: function() {
    return window.mailpoet_i18n;
  }
};
JAVASCRIPT,
      'before'
    );
    $this->wp->wpEnqueueScript('mailpoet_mailpoet');
  }

  private function getPersistedPreferences(): \stdClass {
    $currentUserId = $this->wp->getCurrentUserId();
    if (!$currentUserId) {
      return new \stdClass();
    }

    $preferences = $this->wp->getUserMeta(
      $currentUserId,
      $this->wp->getBlogPrefix() . 'persisted_preferences',
      true
    );

    return is_array($preferences) ? (object)$preferences : new \stdClass();
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
