<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Cron\Workers\SubscriberLimitNotificationWorker;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../../../lib/Cron/Workers/SubscriberLimitNotificationWorker.php';
require_once __DIR__ . '/../../../lib/Subscribers/SubscriberLimitNotificationScheduler.php';

class SubscriberLimitNotificationSchedulerTest extends \MailPoetUnitTest {
  public function testItRegistersDedicatedCountChangeHook(): void {
    $cronWorkerScheduler = $this->createMock(CronWorkerScheduler::class);
    $wp = $this->createMock(WPFunctions::class);
    $scheduler = new SubscriberLimitNotificationScheduler($cronWorkerScheduler, $wp);

    $wp->expects($this->once())
      ->method('addAction')
      ->with(
        SubscriberEntity::HOOK_SUBSCRIBERS_COUNT_CHANGED,
        [$scheduler, 'schedule'],
        10,
        1
      );

    $scheduler->setupHooks();
  }

  public function testItSchedulesSingleWorkerFromCountChangeHook(): void {
    $cronWorkerScheduler = $this->createMock(CronWorkerScheduler::class);
    $cronWorkerScheduler->expects($this->once())
      ->method('scheduleImmediatelyIfNotRunning')
      ->with(SubscriberLimitNotificationWorker::TASK_TYPE);
    $wp = $this->createMock(WPFunctions::class);

    $scheduler = new SubscriberLimitNotificationScheduler($cronWorkerScheduler, $wp);
    $scheduler->schedule([1, 2]);
  }
}
