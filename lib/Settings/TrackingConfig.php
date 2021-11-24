<?php declare(strict_types=1);

namespace MailPoet\Settings;

class TrackingConfig {
  const LEVEL_FULL = 'full';
  const LEVEL_PARTIAL = 'partial';
  const LEVEL_BASIC = 'basic';

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function isEmailTrackingEnabled(): bool {
    return in_array($this->settings->get('tracking.level', self::LEVEL_FULL), [self::LEVEL_PARTIAL, self::LEVEL_FULL], true);
  }

  public function isCookieTrackingEnabled(): bool {
    return $this->settings->get('tracking.level', self::LEVEL_FULL) === self::LEVEL_FULL;
  }
}
