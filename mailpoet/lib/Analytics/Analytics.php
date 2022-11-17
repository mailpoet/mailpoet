<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Analytics;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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

  public function __construct(
    Reporter $reporter,
    SettingsController $settingsController
  ) {
    $this->reporter = $reporter;
    $this->settings = $settingsController;
    $this->wp = new WPFunctions;
  }

  /** @return array|null */
  public function generateAnalytics() {
    if ($this->shouldSend()) {
      $data = $this->wp->applyFilters(self::ANALYTICS_FILTER, $this->reporter->getData());
      $this->recordDataSent();
      return $data;
    }
    return null;
  }

  /** @return bool */
  public function isEnabled() {
    $analyticsSettings = $this->settings->get('analytics', []);
    return !empty($analyticsSettings['enabled']) === true;
  }

  public function setPublicId($newPublicId) {
    $currentPublicId = $this->settings->get('public_id');
    if ($currentPublicId !== $newPublicId) {
      $this->settings->set('public_id', $newPublicId);
      $this->settings->set('new_public_id', 'true');
      // Force user data to be resent
      $this->settings->delete(Analytics::SETTINGS_LAST_SENT_KEY);
    }
  }

  /** @return string */
  public function getPublicId() {
    $publicId = $this->settings->get('public_id', '');
    // if we didn't get the user public_id from the shop yet : we create one based on mixpanel distinct_id
    if (empty($publicId) && !empty($_COOKIE['mixpanel_distinct_id'])) {
      // the public id has to be diffent that mixpanel_distinct_id in order to be used on different browser
      $mixpanelDistinctId = md5(sanitize_text_field(wp_unslash($_COOKIE['mixpanel_distinct_id'])));
      $this->settings->set('public_id', $mixpanelDistinctId);
      $this->settings->set('new_public_id', 'true');
      return $mixpanelDistinctId;
    }
    return $publicId;
  }

  /**
   * Returns true if a the public_id was added and update new_public_id to false
   * @return bool
   */
  public function isPublicIdNew() {
    $newPublicId = $this->settings->get('new_public_id');
    if ($newPublicId === 'true') {
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
