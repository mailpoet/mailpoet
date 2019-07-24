<?php

namespace MailPoet\Referrals;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class ReferralDetector {
  const REFERRAL_CONSTANT_NAME = 'MAILPOET_REFERRAL_ID';
  const REFERRAL_SETTING_NAME = 'referral_id';

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  function __construct(WPFunctions $wp, SettingsController $settings) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  function detect() {
    $referral_id = $this->settings->get(self::REFERRAL_SETTING_NAME, null);
    if ($referral_id) {
      return $referral_id;
    }
    $referral_id = $this->wp->getOption(self::REFERRAL_CONSTANT_NAME, null);
    if ($referral_id === null && defined(self::REFERRAL_CONSTANT_NAME)) {
      $referral_id = constant(self::REFERRAL_CONSTANT_NAME);
    }
    if ($referral_id !== null) {
      $this->settings->set(self::REFERRAL_SETTING_NAME, $referral_id);
    }
    return $referral_id;
  }
}
