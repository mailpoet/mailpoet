<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Mailer\Mailer;
use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;

class Migration_20260902_130046_App extends AppMigration {
  private const REMOVED_REGION = 'cn-north-1';
  private const REPLACEMENT_REGION = 'cn-northwest-1';

  public function run(): void {
    $settings = $this->container->get(SettingsController::class);
    if (!$settings->hasSavedValue('db_version')) {
      return;
    }

    $config = $settings->get(Mailer::MAILER_CONFIG_SETTING_NAME);
    if (!is_array($config) || ($config['method'] ?? null) !== Mailer::METHOD_AMAZONSES) {
      return;
    }

    if (($config['region'] ?? null) !== self::REMOVED_REGION) {
      return;
    }

    // Ningxia is the only China region with an SES endpoint, so it is the one
    // place a site set to Beijing has any chance of sending from.
    $config['region'] = self::REPLACEMENT_REGION;
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, $config);
  }
}
