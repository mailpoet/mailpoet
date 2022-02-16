<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class Localizer {
  public function init() {
    $this->loadGlobalText();
    $this->loadPluginText();
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

  public function loadPluginText() {
    WPFunctions::get()->loadPluginTextdomain(
      Env::$pluginName,
      false,
      dirname(plugin_basename(Env::$file)) . '/lang/'
    );
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
