<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util;

use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class Installation {
  const NEW_INSTALLATION_DAYS_LIMIT = 30;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function isNewInstallation() {
    $installedAt = $this->settings->get('installed_at');
    if (is_null($installedAt)) {
      return true;
    }
    $installedAt = Carbon::createFromTimestamp(strtotime($installedAt));
    $currentTime = Carbon::now()->millisecond(0);
    return $currentTime->diffInDays($installedAt) <= self::NEW_INSTALLATION_DAYS_LIMIT;
  }
}
