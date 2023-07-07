<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Migrator\DbMigration;
use MailPoet\Settings\SettingsController;

class Migration_20230425_211517 extends DbMigration {
  public function run(): void {
    $settingsController = $this->container->get(SettingsController::class);
    $possibleKeys = [
      'subscribe.on_register.label',
      'subscribe.on_comment.label',
    ];
    $default = __('Yes, add me to your mailing list', 'mailpoet');
    foreach ($possibleKeys as $key) {
      $currentValue = $settingsController->get($key);
      if ($currentValue === 'TRANSLATION "yesAddMe" NOT FOUND') {
        $settingsController->set($key, $default);
      }
    }
  }
}
