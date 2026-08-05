<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\TrackingConsentController;

class Migration_20260805_120000_App extends AppMigration {
  /**
   * The Yes/No toggle the three-state "Subscriber choice" setting replaces.
   * Read once here and then ignored; the value is left in place so a downgrade
   * still finds what it wrote.
   */
  const LEGACY_TRACK_UNKNOWN = 'tracking.consent.track_unknown';

  public function run(): void {
    $settings = $this->container->get(SettingsController::class);

    // New installs start at the code default; there is nothing to carry over.
    if (!$settings->hasSavedValue('db_version')) {
      return;
    }

    // A site that deliberately turned the old toggle off was running strict
    // opt-in, so it keeps asking everyone. Every other existing site — the vast
    // majority, who never touched it — moves to the new default and loses the
    // manage-subscription control it never asked for.
    $trackedUnknownSubscribers = (bool)$settings->get(self::LEGACY_TRACK_UNKNOWN, true);

    $settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      $trackedUnknownSubscribers
        ? TrackingConsentController::CHOICE_TRACK_ALL
        : TrackingConsentController::CHOICE_ASK_ALL
    );
  }
}
