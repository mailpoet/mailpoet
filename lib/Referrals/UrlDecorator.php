<?php

namespace MailPoet\Referrals;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class UrlDecorator {

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  function __construct(WPFunctions $wp, SettingsController $settings) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  function decorate($url) {
    $referral_id = $this->settings->get(ReferralDetector::REFERRAL_SETTING_NAME, null);
    if ($referral_id === null) {
      return $url;
    }
    return $this->wp->addQueryArg('ref', $referral_id, $url);
  }
}
