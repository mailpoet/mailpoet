<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\InactiveSubscribersMaintenance;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Migrator\AppMigration;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Connection;

class Migration_20260623_120000_App extends AppMigration {
  public function run(): void {
    $connection = $this->container->get(Connection::class);
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $connection->executeStatement(
      "
      DELETE FROM {$scheduledTasksTable}
      WHERE type IN (:types)
      AND (status != 'completed' OR status IS NULL)
      ",
      [
        'types' => [
          'subscribers_email_count',
          'inactive_subscribers',
        ],
      ],
      [
        'types' => ArrayParameterType::STRING,
      ]
    );

    $scheduler = $this->container->get(CronWorkerScheduler::class);
    $scheduler->schedule(
      InactiveSubscribersMaintenance::TASK_TYPE,
      Carbon::now()->millisecond(0)->addHour()
    );
  }
}
