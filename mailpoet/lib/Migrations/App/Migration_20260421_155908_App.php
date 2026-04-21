<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Notices\SendingQueueBodyCleanupNotice;

class Migration_20260421_155908_App extends AppMigration {
  public function run(): void {
    $settings = $this->container->get(SettingsController::class);
    $settings->set(SendingQueueBodyCleanupNotice::OPTION_NAME, true);
  }
}
