<?php

namespace MailPoet\Analytics;

use Carbon\Carbon;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Settings\SettingsController;

if (!defined('ABSPATH')) exit;

class Analytics {

  const SETTINGS_LAST_SENT_KEY = 'analytics_last_sent';
  const SEND_AFTER_DAYS = 7;
  const ANALYTICS_FILTER = 'mailpoet_analytics';

  /** @var Reporter */
  private $reporter;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(Reporter $reporter, SettingsController $settingsController) {
    $this->reporter = $reporter;
    $this->settings = $settingsController;
    $this->wp = new WPFunctions;
  }

  /** @return array */
  function generateAnalytics() {
    if ($this->shouldSend()) {
      $data = $this->wp->applyFilters(self::ANALYTICS_FILTER, $this->reporter->getData());
      $this->recordDataSent();
      return $data;
    }
  }

  /** @return boolean */
  function isEnabled() {
    $analytics_settings = $this->settings->get('analytics', []);
    return !empty($analytics_settings['enabled']) === true;
  }

  static function setPublicId($new_public_id) {
    $settings = new SettingsController();
    $current_public_id = $settings->get('public_id');
    if ($current_public_id !== $new_public_id) {
      $settings->set('public_id', $new_public_id);
      $settings->set('new_public_id', 'true');
      // Force user data to be resent
      $settings->delete(Analytics::SETTINGS_LAST_SENT_KEY);
    }
  }

  /** @return string */
  function getPublicId() {
    $public_id = $this->settings->get('public_id', '');
    // if we didn't get the user public_id from the shop yet : we create one based on mixpanel distinct_id
    if (empty($public_id) && !empty($_COOKIE['mixpanel_distinct_id'])) {
      // the public id has to be diffent that mixpanel_distinct_id in order to be used on different browser
      $mixpanel_distinct_id = md5($_COOKIE['mixpanel_distinct_id']);
      $this->settings->set('public_id', $mixpanel_distinct_id);
      $this->settings->set('new_public_id', 'true');
      return $mixpanel_distinct_id;
    }
    return $public_id;
  }

  /**
   * Returns true if a the public_id was added and update new_public_id to false
   * @return boolean
   */
  function isPublicIdNew() {
    $new_public_id = $this->settings->get('new_public_id');
    if ($new_public_id === 'true') {
      $this->settings->set('new_public_id', 'false');
      return true;
    }
    return false;
  }

  private function shouldSend() {
    if (!$this->isEnabled()) {
      return false;
    }
    $lastSent = $this->settings->get(Analytics::SETTINGS_LAST_SENT_KEY);
    if (!$lastSent) {
      return true;
    }
    $lastSentCarbon = Carbon::createFromTimestamp(strtotime($lastSent))->addDays(Analytics::SEND_AFTER_DAYS);
    return $lastSentCarbon->isPast();
  }

  private function recordDataSent() {
    $this->settings->set(Analytics::SETTINGS_LAST_SENT_KEY, Carbon::now());
  }

}
