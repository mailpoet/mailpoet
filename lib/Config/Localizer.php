<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Localizer {
  function __construct($renderer) {
    $this->renderer = $renderer;
  }

  function init() {
    add_action(
      'init',
      array($this, 'setup')
    );
  }

  function setup() {
    $this->loadGlobalText();
    $this->loadPluginText();
    $this->setGlobalRtl();
  }

  function loadGlobalText() {
    $language_path =
      Env::$languages_path
      . '/'
      . $this->locale()
      . '.mo';
    load_textdomain(Env::$plugin_name, $language_path);
  }

  function loadPluginText() {
    load_plugin_textdomain(
      Env::$plugin_name,
      false,
      dirname(plugin_basename(Env::$file)) . '/lang/'
    );
  }

  function setGlobalRtl() {
    $this->renderer->addGlobal('is_rtl', is_rtl());
  }

  function locale() {
    $locale = apply_filters(
      'plugin_locale',
      get_locale(),
      Env::$plugin_name
    );
    return $locale;
  }
}
