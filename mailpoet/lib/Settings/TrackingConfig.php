<?php declare(strict_types = 1);

namespace MailPoet\Settings;

class TrackingConfig {
  const LEVEL_FULL = 'full';
  const LEVEL_PARTIAL = 'partial';
  const LEVEL_BASIC = 'basic';

  const OPENS_MERGED = 'merged';
  const OPENS_SEPARATED = 'separated';

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function isEmailTrackingEnabled(string $level = null): bool {
    $level = $level ?? $this->settings->get('tracking.level', self::LEVEL_FULL);
    return in_array($level, [self::LEVEL_PARTIAL, self::LEVEL_FULL], true);
  }

  public function isCookieTrackingEnabled(string $level = null): bool {
    $level = $level ?? $this->settings->get('tracking.level', self::LEVEL_FULL);
    return $level === self::LEVEL_FULL;
  }

  public function areOpensMerged(string $opens = null): bool {
    $opens = $opens ?? $this->settings->get('tracking.opens', self::OPENS_MERGED);
    return $opens !== self::OPENS_SEPARATED;
  }

  public function areOpensSeparated(string $opens = null): bool {
    return !$this->areOpensMerged($opens);
  }

  public function getConfig(): array {
    return [
      'level' => $this->settings->get('tracking.level', self::LEVEL_FULL),
      'emailTrackingEnabled' => $this->isEmailTrackingEnabled(),
      'cookieTrackingEnabled' => $this->isCookieTrackingEnabled(),
      'opens' => $this->settings->get('tracking.opens', self::OPENS_MERGED),
      'opensMerged' => $this->areOpensMerged(),
      'opensSeparated' => $this->areOpensSeparated(),
    ];
  }
}
