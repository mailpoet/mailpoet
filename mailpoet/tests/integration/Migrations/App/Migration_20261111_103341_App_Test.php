<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Cron\Workers\LogCleanup;
use MailPoet\Cron\Workers\Mixpanel;
use MailPoet\Cron\Workers\Tracks;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20261111_103341_App_Test extends \MailPoetTest {
  public function testItDeletesOnlyCompletedAnalyticsTasks() {
    $factory = new ScheduledTaskFactory();
    $factory->create(Mixpanel::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());
    $factory->create(Mixpanel::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subHour());
    $factory->create(Tracks::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());
    $scheduledMixpanel = $factory->create(Mixpanel::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->addDay());
    $completedOther = $factory->create(LogCleanup::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED, Carbon::now()->subDay());

    (new Migration_20261111_103341_App($this->diContainer))->run();

    $this->entityManager->clear();
    $remainingIds = array_map(function (ScheduledTaskEntity $task): ?int {
      return $task->getId();
    }, $this->diContainer->get(ScheduledTasksRepository::class)->findAll());
    sort($remainingIds);
    verify($remainingIds)->equals([$scheduledMixpanel->getId(), $completedOther->getId()]);
  }
}
