<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Migrator\Migration;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Doctrine\DBAL\Connection;

class Migration_20221124_131445 extends Migration {
  public function run(): void {
    $segmentsTable = $this->getTableName(SegmentEntity::class);
    $columnName = 'display_in_manage_subscription_page';

    if (!$this->columnExists($segmentsTable, $columnName)) {
      $this->connection->executeStatement("
        ALTER TABLE {$segmentsTable}
        ADD {$columnName} tinyint(1) NOT NULL DEFAULT 0
      ");
    }

    $settings = $this->container->get(SettingsController::class);
    $segmentIds = $settings->get('subscription.segments', []);
    if ($segmentIds) {
      // display only segments from settings.subscription.segments
      $this->connection->executeStatement("
        UPDATE {$segmentsTable}
        SET {$columnName} = 1
        WHERE id IN (?)
      ", [$segmentIds], [Connection::PARAM_INT_ARRAY]);

      $settings->set('subscription.segments', []);
    } else {
      $this->connection->executeStatement("
        UPDATE {$segmentsTable}
        SET {$columnName} = 1
        WHERE type = ?
      ", [SegmentEntity::TYPE_DEFAULT]);
    }
  }
}
