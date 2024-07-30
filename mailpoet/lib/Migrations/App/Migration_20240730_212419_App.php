<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Config\Hooks;
use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Subscription;

class Migration_20240730_212419_App extends AppMigration {
  private SettingsController $settings;

  public function run(): void {
    $this->settings = $this->container->get(SettingsController::class);

    // Skip if the installed version is newer than the release that preceded this migration, or if it's a fresh install
    $currentlyInstalledVersion = (string)$this->settings->get('db_version', '4.58.1');
    if (version_compare($currentlyInstalledVersion, '4.58.0', '>')) {
      return;
    }
    // Skip if the opt-in checkbox is not enabled. When enabling, the user chooses
    // the position of the opt-in checkbox on the same settings page, so no default
    // value is necessary.
    if (!$this->settings->get(Subscription::OPTIN_ENABLED_SETTING_NAME, false)) {
      return;
    }
    $this->settings->set(Subscription::OPTIN_POSITION_SETTING_NAME, Hooks::OPTIN_POSITION_BEFORE_TERMS_AND_CONDITIONS);
  }
}
