<?php declare(strict_types = 1);

namespace MailPoet\Settings;

use MailPoet\WP\Functions as WPFunctions;

class TrackingConfig {
  const LEVEL_FULL = 'full';
  const LEVEL_PARTIAL = 'partial';
  const LEVEL_BASIC = 'basic';

  const OPENS_MERGED = 'merged';
  const OPENS_SEPARATED = 'separated';

  private SettingsController $settings;

  private WPFunctions $wp;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function isEmailTrackingEnabled(?string $level = null): bool {
    $level = $level ?? $this->settings->get('tracking.level', self::LEVEL_FULL);
    return in_array($level, [self::LEVEL_PARTIAL, self::LEVEL_FULL], true);
  }

  public function isCookieTrackingEnabled(?string $level = null): bool {
    $level = $level ?? $this->settings->get('tracking.level', self::LEVEL_FULL);
    return (bool)$this->wp->applyFilters('mailpoet_is_cookie_tracking_enabled', $level === self::LEVEL_FULL);
  }

  public function areOpensMerged(?string $opens = null): bool {
    $opens = $opens ?? $this->settings->get('tracking.opens', self::OPENS_MERGED);
    return $opens !== self::OPENS_SEPARATED;
  }

  public function areOpensSeparated(?string $opens = null): bool {
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
