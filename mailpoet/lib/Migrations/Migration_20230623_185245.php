<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Cron\Workers\BackfillEngagementData;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Migrator\Migration;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Migration_20230623_185245 extends Migration {
  public function run(): void {
    $scheduledTasksRepository = $this->container->get(ScheduledTasksRepository::class);
    $task = $scheduledTasksRepository->findOneBy(
      [
        'type' => BackfillEngagementData::TASK_TYPE,
        'status' => [ScheduledTaskEntity::STATUS_SCHEDULED, null],
      ]
    );

    if ($task) {
      return;
    }

    $wp = $this->container->get(WPFunctions::class);
    $task = new ScheduledTaskEntity();
    $task->setType(BackfillEngagementData::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $now = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    $task->setScheduledAt($now);
    $scheduledTasksRepository->persist($task);
    $scheduledTasksRepository->flush();
  }
}
