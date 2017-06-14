<?php
namespace MailPoet\Analytics;
use Carbon\Carbon;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Analytics {

  const SETTINGS_LAST_SENT_KEY = "analytics_last_sent";
  const SEND_AFTER_DAYS = 7;

  /** @var Setting */
  private $settings;

  public function __construct(Setting $settings) {
    $this->settings = $settings;
  }

  /** @return array */
  function getData() {
    if($this->shouldSend()) {
      $analytics = new Reporter();
      $data = $analytics->getData();
      $this->recordDataSent();
      return $data;
    }
  }

  /** @return boolean */
  function isEnabled() {
    $analytics_settings = $this->settings->getValue('analytics', array());
    return $analytics_settings["enabled"] === "1";
  }

  private function shouldSend() {
    if(!$this->isEnabled()) {
      return false;
    }
    $lastSent = $this->settings->getValue(Analytics::SETTINGS_LAST_SENT_KEY);
    if(!$lastSent) {
      return true;
    }
    $lastSentCarbon = Carbon::createFromTimestamp(strtotime($lastSent))->addDays(Analytics::SEND_AFTER_DAYS);
    return $lastSentCarbon->isPast();
  }

  private function recordDataSent() {
    $this->settings->setValue(Analytics::SETTINGS_LAST_SENT_KEY, Carbon::create());
  }

}
