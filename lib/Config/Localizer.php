<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class Localizer {
  public function init() {
    $this->loadGlobalText();
    $this->loadPluginText();
  }

  public function loadGlobalText() {
    $language_path = sprintf(
      '%s/%s-%s.mo',
      Env::$languages_path,
      Env::$plugin_name,
      $this->locale()
    );
    WPFunctions::get()->loadTextdomain(Env::$plugin_name, $language_path);
  }

  public function loadPluginText() {
    WPFunctions::get()->loadPluginTextdomain(
      Env::$plugin_name,
      false,
      dirname(plugin_basename(Env::$file)) . '/lang/'
    );
  }

  public function locale() {
    $locale = WPFunctions::get()->applyFilters(
      'plugin_locale',
      WPFunctions::get()->getUserLocale(),
      Env::$plugin_name
    );
    return $locale;
  }

  public function forceLoadWebsiteLocaleText() {
    $language_path = sprintf(
      '%s/%s-%s.mo',
      Env::$languages_path,
      Env::$plugin_name,
      WPFunctions::get()->getLocale()
    );
    WPFunctions::get()->unloadTextdomain(Env::$plugin_name);
    WPFunctions::get()->loadTextdomain(Env::$plugin_name, $language_path);
  }
}
