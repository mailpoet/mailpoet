<?php

namespace MailPoet\Analytics;

use Carbon\Carbon;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Analytics {

  const SETTINGS_LAST_SENT_KEY = 'analytics_last_sent';
  const SEND_AFTER_DAYS = 7;

  /** @var Reporter */
  private $reporter;

  public function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
  }

  /** @return array */
  function generateAnalytics() {
    if($this->shouldSend()) {
      $data = $this->reporter->getData();
      $this->recordDataSent();
      return $data;
    }
  }

  /** @return boolean */
  function isEnabled() {
    $analytics_settings = Setting::getValue('analytics', array());
    return !empty($analytics_settings['enabled']) === true;
  }

  private function shouldSend() {
    if(!$this->isEnabled()) {
      return false;
    }
    $lastSent = Setting::getValue(Analytics::SETTINGS_LAST_SENT_KEY);
    if(!$lastSent) {
      return true;
    }
    $lastSentCarbon = Carbon::createFromTimestamp(strtotime($lastSent))->addDays(Analytics::SEND_AFTER_DAYS);
    return $lastSentCarbon->isPast();
  }

  private function recordDataSent() {
    Setting::setValue(Analytics::SETTINGS_LAST_SENT_KEY, Carbon::now());
  }

}
