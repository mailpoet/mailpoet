<?php
namespace MailPoet\Config;
use MailPoet\Analytics\Reporter;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Analytics {

  /** @return array */
  function getData() {
    if($this->isEnabled()) {
      $analytics = new Reporter();
      return $analytics->getData();
    }
  }

  /** @return boolean */
  function isEnabled() {
    $analytics_settings = Setting::getValue('analytics', array());
    return $analytics_settings["enabled"] === "1";
  }
}
