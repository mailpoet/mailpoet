<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\InactiveSubscribersMaintenance;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Migrator\AppMigration;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Connection;

class Migration_20260623_120000_App extends AppMigration {
  public function run(): void {
    $connection = $this->container->get(Connection::class);
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();

    // Seed the email-count baseline from the last completed legacy task so the new worker
    // continues incrementally instead of doing a full recount. Afterwards the worker only
    // reads the setting -- it never looks at the legacy tasks again.
    $this->seedEmailCountBaseline($connection, $scheduledTasksTable);

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

  private function seedEmailCountBaseline(Connection $connection, string $scheduledTasksTable): void {
    $lastCompletedScheduledAt = $connection->executeQuery(
      "
      SELECT scheduled_at FROM {$scheduledTasksTable}
      WHERE type = 'subscribers_email_count'
      AND status = 'completed'
      AND deleted_at IS NULL
      AND scheduled_at IS NOT NULL
      ORDER BY scheduled_at DESC
      LIMIT 1
      "
    )->fetchOne();

    if (!is_string($lastCompletedScheduledAt) || $lastCompletedScheduledAt === '') {
      return;
    }

    $settings = $this->container->get(SettingsController::class);
    $settings->set(
      InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING,
      Carbon::parse($lastCompletedScheduledAt)->format(\DateTimeInterface::ATOM)
    );
  }
}
