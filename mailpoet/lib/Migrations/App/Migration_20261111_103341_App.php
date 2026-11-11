<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Migrator\AppMigration;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Connection;

/**
 * With analytics sharing off, the Mixpanel and Tracks workers used to complete a task on almost
 * every cron run. Nothing reads the completed tasks, so this deletes them in batches.
 */
class Migration_20261111_103341_App extends AppMigration {
  private const BATCH_SIZE = 10000;

  public function run(): void {
    $connection = $this->container->get(Connection::class);
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();

    do {
      $deleted = (int)$connection->executeStatement(
        "
        DELETE FROM {$scheduledTasksTable}
        WHERE type IN (:types)
        AND status = 'completed'
        LIMIT " . self::BATCH_SIZE,
        ['types' => ['mixpanel', 'tracks']],
        ['types' => ArrayParameterType::STRING]
      );
    } while ($deleted >= self::BATCH_SIZE);
  }
}
