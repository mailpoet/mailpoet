<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Notices\SendingQueueBodyCleanupNotice;

class Migration_20260421_155908_App extends AppMigration {
  public function run(): void {
    $settings = $this->container->get(SettingsController::class);
    // Skip for new installs — they don't need a notice about behavior they've never known
    if (version_compare((string)$settings->get('db_version', '5.23.3'), '5.23.2', '>')) {
      return;
    }
    $settings->set(SendingQueueBodyCleanupNotice::OPTION_NAME, true);
  }
}
