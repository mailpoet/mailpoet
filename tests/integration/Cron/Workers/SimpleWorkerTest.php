<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SimpleWorkerMockImplementation as MockSimpleWorker;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

require_once __DIR__ . '/SimpleWorkerMockImplementation.php';

class SimpleWorkerTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->worker = new MockSimpleWorker();
  }

  function testItRequiresTaskTypeToConstruct() {
    $worker = Stub::make(
      'MailPoet\Cron\Workers\SimpleWorker',
      [],
      $this
    );
    try {
      $worker_class = get_class($worker);
      new $worker_class();
      $this->fail('SimpleWorker did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Constant TASK_TYPE is not defined on subclass ' . $worker_class);
    }
  }

  function testItSchedulesTask() {
    expect(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->isEmpty();
    (new MockSimpleWorker())->schedule();
    expect(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany())->notEmpty();
  }

  function testItDoesNotScheduleTaskTwice() {
    $worker = new MockSimpleWorker();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(0);
    $worker->schedule();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
    $worker->schedule();
    expect(count(ScheduledTask::where('type', MockSimpleWorker::TASK_TYPE)->findMany()))->equals(1);
  }

  function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $current_date = Carbon::createFromTimestamp(current_time('timestamp'));
    $next_run_date = (new MockSimpleWorker())->getNextRunDate();
    $difference = $next_run_date->diffInDays($current_date);
    // Subtract days left in the current week
    $difference -= (Carbon::DAYS_PER_WEEK - $current_date->format('N'));
    expect($difference)->lessOrEquals(7);
    expect($difference)->greaterOrEquals(0);
  }

  function _after() {
    $this->di_container->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
