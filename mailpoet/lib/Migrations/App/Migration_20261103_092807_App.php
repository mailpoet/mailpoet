<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

/**
 * Obsolete link tokens are now accepted for 1 month after the link token upgrade instead of 12.
 * Sites that already upgraded saved a deadline 12 months ahead; this moves it to at most 1 month from now.
 */
class Migration_20261103_092807_App extends AppMigration {
  // Kept literal so later changes in LinkTokens don't affect this migration
  private const OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING = 'obsolete_link_tokens_accepted_until';
  private const GRACE_PERIOD_MONTHS = 1;

  public function run(): void {
    $settings = $this->container->get(SettingsController::class);
    if (!$settings->hasSavedValue(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING)) {
      return;
    }
    $acceptedUntil = (int)$settings->get(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    $latestAcceptedUntil = Carbon::now()->addMonths(self::GRACE_PERIOD_MONTHS)->getTimestamp();
    if ($acceptedUntil > $latestAcceptedUntil) {
      $settings->set(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, $latestAcceptedUntil);
    }
  }
}
