<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class Localizer {
  public function init(WPFunctions $wpFunctions) {
    $this->loadGlobalText();
    $this->setupTranslationsUpdater($wpFunctions);
  }

  private function setupTranslationsUpdater(WPFunctions $wpFunctions) {
    $translationUpdater = $this->getUpdater($wpFunctions);
    $translationUpdater->init();
  }

  public function loadGlobalText() {
    $languagePath = sprintf(
      '%s/%s-%s.mo',
      Env::$languagesPath,
      Env::$pluginName,
      $this->locale()
    );
    WPFunctions::get()->loadTextdomain(Env::$pluginName, $languagePath);
  }

  public function locale() {
    // Reading the user locale resolves the current user. Before 'plugins_loaded'
    // (e.g. when network activated on multisite) the auth cookie constants may
    // not be defined yet, and resolving the user fatals on PHP 8 if pluggable.php
    // was already loaded by another early component. Fall back to the site
    // locale; Initializer::setupUserLocale() switches to the user locale on
    // 'wp_loaded'.
    $defaultLocale = WPFunctions::get()->didAction('plugins_loaded')
      ? WPFunctions::get()->getUserLocale()
      : WPFunctions::get()->getLocale();
    $locale = WPFunctions::get()->applyFilters(
      'plugin_locale',
      $defaultLocale,
      Env::$pluginName
    );
    return $locale;
  }

  public function forceInstallLanguagePacks(WPFunctions $wpFunctions) {
    $translationUpdater = $this->getUpdater($wpFunctions);
    // Add MailPoet translation update to the update_plugins site transient via inner hook
    $transient = $translationUpdater->checkForTranslations(new \stdClass());
    $mailpoetTranslations = [];
    $translationUpdates = $transient->translations ?? [];
    foreach ($translationUpdates as $translationUpdate) {
      $mailpoetTranslations[] = (object)$translationUpdate;
    }

    if (!empty($mailpoetTranslations)) {
      require_once ABSPATH . '/wp-admin/includes/file.php';
      require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
      $upgrader = new \Language_Pack_Upgrader(new SilentUpgraderSkin());
      $upgrader->bulk_upgrade($mailpoetTranslations);
    }
  }

  public function forceLoadWebsiteLocaleText() {
    $languagePath = sprintf(
      '%s/%s-%s.mo',
      Env::$languagesPath,
      Env::$pluginName,
      WPFunctions::get()->getLocale()
    );
    WPFunctions::get()->unloadTextdomain(Env::$pluginName);
    WPFunctions::get()->loadTextdomain(Env::$pluginName, $languagePath);
  }

  private function getUpdater(WPFunctions $wp): TranslationUpdater {
    $premiumSlug = Installer::PREMIUM_PLUGIN_SLUG;
    $premiumVersion = defined('MAILPOET_PREMIUM_VERSION') ? MAILPOET_PREMIUM_VERSION : null;
    $freeSlug = Env::$pluginName;
    $freeVersion = MAILPOET_VERSION;

    return new TranslationUpdater(
      $wp,
      $freeSlug,
      $freeVersion,
      $premiumSlug,
      $premiumVersion
    );
  }
}
