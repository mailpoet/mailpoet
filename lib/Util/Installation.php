<?php
namespace MailPoet\Util;

use Carbon\Carbon;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Settings\SettingsController;

class Installation {
  const NEW_INSTALLATION_DAYS_LIMIT = 30;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(SettingsController $settings, WPFunctions $wp) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function isNewInstallation() {
    $installed_at = $this->settings->get('installed_at');
    if (is_null($installed_at)) {
      return true;
    }
    $installed_at = Carbon::createFromTimestamp(strtotime($installed_at));
    $current_time = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    return $current_time->diffInDays($installed_at) <= self::NEW_INSTALLATION_DAYS_LIMIT;
  }
}
