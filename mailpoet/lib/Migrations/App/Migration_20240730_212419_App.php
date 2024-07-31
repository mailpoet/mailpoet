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

    // Skip if the opt-in checkbox is not enabled or the position is already set.
    // When enabling, the user chooses the position of the opt-in checkbox
    // on the same settings page.
    $optInEnabled = $this->settings->get(Subscription::OPTIN_ENABLED_SETTING_NAME, false);
    $optInPosition = $this->settings->get(Subscription::OPTIN_POSITION_SETTING_NAME, null);
    if (!$optInEnabled || $optInPosition) {
      return;
    }
    // Set previous default value for existing installations
    $this->settings->set(Subscription::OPTIN_POSITION_SETTING_NAME, Hooks::OPTIN_POSITION_BEFORE_TERMS_AND_CONDITIONS);
  }
}
