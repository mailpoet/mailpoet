<?php declare(strict_types = 1);

namespace MailPoet\Mailer;

use MailPoet\Settings\SettingsController;

/**
 * Super Sending sends MSS bulk batches as one template plus per-recipient substitutions.
 * The setting stores who turned it on or off last in `source`, so we can tell a user's own
 * choice from a default we set for them.
 */
class SuperSendingController {
  public const SETTING_NAME = 'mss_super_sending';
  public const SOURCE_DEFAULT = 'default';
  public const SOURCE_MANUAL = 'manual';

  private SettingsController $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function isEnabled(): bool {
    return (bool)$this->settings->get(self::SETTING_NAME . '.enabled');
  }

  /**
   * Call after the settings page saved the setting, with its value from before the save.
   * The page sends only `enabled`, so the stored source is kept unless `enabled` changed.
   */
  public function onSettingSaved(array $previousValue): void {
    $wasEnabled = (bool)($previousValue['enabled'] ?? false);
    $isEnabled = $this->isEnabled();
    if ($wasEnabled === $isEnabled) {
      $this->settings->set(self::SETTING_NAME, $previousValue);
      return;
    }
    $this->settings->set(self::SETTING_NAME, [
      'enabled' => $isEnabled ? '1' : '',
      'source' => self::SOURCE_MANUAL,
    ]);
  }
}
