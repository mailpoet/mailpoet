<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Analytics\Analytics;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class MixpanelTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var Mixpanel */
  private $worker;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->cronWorkerRunner = new CronWorkerRunner(
      $this->diContainer->get(CronHelper::class),
      $this->diContainer->get(CronWorkerScheduler::class),
      $this->scheduledTasksRepository,
      $this->diContainer->get(LoggerFactory::class)
    );
    $this->worker = $this->diContainer->get(Mixpanel::class);
    $this->settings->set(Analytics::SETTINGS_LAST_SENT_KEY, Carbon::now()->subDays(30)->toDateTimeString());
  }

  public function testItRemovesDueTaskWithoutSchedulingAnotherWhileSharingIsOff(): void {
    $this->settings->set('analytics', ['enabled' => '']);
    (new ScheduledTaskFactory())->create(Mixpanel::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subMinute());

    $this->cronWorkerRunner->run($this->worker);
    $this->cronWorkerRunner->run($this->worker);

    verify($this->scheduledTasksRepository->findBy(['type' => Mixpanel::TASK_TYPE]))->empty();
  }

  public function testItSchedulesDueTaskWhenSharingIsOnAndDataIsOutdated(): void {
    $this->settings->set('analytics', ['enabled' => '1']);

    $this->cronWorkerRunner->run($this->worker);

    $tasks = $this->scheduledTasksRepository->findBy(['type' => Mixpanel::TASK_TYPE]);
    verify($tasks)->arrayCount(1);
    verify($tasks[0]->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    verify($tasks[0]->getScheduledAt())->lessThan(Carbon::now());
  }
}
