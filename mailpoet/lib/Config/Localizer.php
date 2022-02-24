<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class Localizer {
  public function init(WPFunctions $wpFunctions) {
    $this->loadGlobalText();
    $this->setupTranslationsUpdater($wpFunctions);
  }

  private function setupTranslationsUpdater(WPFunctions $wpFunctions) {
    $premiumSlug = Installer::PREMIUM_PLUGIN_SLUG;
    $premiumVersion = defined('MAILPOET_PREMIUM_VERSION') ? MAILPOET_PREMIUM_VERSION : null;
    $freeSlug = Env::$pluginName;
    $freeVersion = MAILPOET_VERSION;

    $translationUpdater = new TranslationUpdater(
      $wpFunctions,
      $freeSlug,
      $freeVersion,
      $premiumSlug,
      $premiumVersion
    );
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
    $locale = WPFunctions::get()->applyFilters(
      'plugin_locale',
      WPFunctions::get()->getUserLocale(),
      Env::$pluginName
    );
    return $locale;
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
}
