<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\TrackingConsentController;

class Migration_20260819_120000_App extends AppMigration {
  /**
   * Give sites already asking everyone a strict-since stamp (STOMAIL-8310).
   *
   * Tracked-only rates treat a never-asked recipient as untracked only for
   * emails sent after strict consent was switched on. Sites that switched
   * before this release have no stamp, so without one their existing campaigns
   * would suddenly exclude recipients who were tracked at the time — the same
   * recipients whose opens and clicks are already recorded, which pushes those
   * rates above 100%.
   *
   * Stamping "now" freezes every past campaign at the numbers the site already
   * sees, and lets the rule apply from here on.
   */
  public function run(): void {
    $settings = $this->container->get(SettingsController::class);

    // New installs have nothing to preserve; they get stamped if and when they
    // switch, through the settings save path.
    if (!$settings->hasSavedValue('db_version')) {
      return;
    }

    if ($settings->get(TrackingConsentController::SETTING_SUBSCRIBER_CHOICE) !== TrackingConsentController::CHOICE_ASK_ALL) {
      return;
    }

    // Never overwrite a stamp that is already there.
    if ($settings->get(TrackingConsentController::SETTING_STRICT_SINCE)) {
      return;
    }

    $settings->set(
      TrackingConsentController::SETTING_STRICT_SINCE,
      (new \DateTimeImmutable())->format('Y-m-d H:i:s')
    );
  }
}
