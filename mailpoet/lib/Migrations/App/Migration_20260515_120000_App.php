<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;

class Migration_20260515_120000_App extends AppMigration {
  public function run(): void {
    $settings = $this->container->get(SettingsController::class);
    if (!$settings->hasSavedValue('db_version')) {
      return;
    }
    if ($settings->hasSavedValue('subscription.manage_subscription_page_style')) {
      return;
    }

    $settings->set(
      'subscription.manage_subscription_page_style',
      SettingsController::MANAGE_SUBSCRIPTION_PAGE_STYLE_CLASSIC
    );
  }
}
