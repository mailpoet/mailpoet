<?php

namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Localizer {
  function init() {
    $this->loadGlobalText();
    $this->loadPluginText();
  }

  function loadGlobalText() {
    $language_path = sprintf(
      '%s/%s.mo',
      Env::$languages_path,
      $this->locale()
    );
    load_textdomain(Env::$plugin_name, $language_path);
  }

  function loadPluginText() {
    load_plugin_textdomain(
      Env::$plugin_name,
      false,
      dirname(plugin_basename(Env::$file)) . '/lang/'
    );
  }

  function locale() {
    $locale = apply_filters(
      'plugin_locale',
      get_user_locale(),
      Env::$plugin_name
    );
    return $locale;
  }

  function forceLoadWebsiteLocaleText() {
    $language_path = sprintf(
      '%s/%s-%s.mo',
      Env::$languages_path,
      Env::$plugin_name,
      get_locale()
    );
    unload_textdomain(Env::$plugin_name);
    load_textdomain(Env::$plugin_name, $language_path);
  }
}
