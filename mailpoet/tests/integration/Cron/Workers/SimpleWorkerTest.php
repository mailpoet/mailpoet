<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SimpleWorkerMockImplementation as MockSimpleWorker;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/SimpleWorkerMockImplementation.php';

class SimpleWorkerTest extends \MailPoetTest {
  public $worker;
  public $cronHelper;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->cronHelper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->worker = new MockSimpleWorker();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItRequiresTaskTypeToConstruct() {
    $worker = Stub::make(
      'MailPoet\Cron\Workers\SimpleWorker',
      [],
      $this
    );
    $workerClass = get_class($worker);
    try {
      new $workerClass();
      $this->fail('SimpleWorker did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Constant TASK_TYPE is not defined on subclass ' . $workerClass);
    }
  }

  public function testItSchedulesTask() {
    expect($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE]))->isEmpty();
    (new MockSimpleWorker())->schedule();
    expect($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE]))->notEmpty();
  }

  public function testItDoesNotScheduleTaskTwice() {
    $worker = new MockSimpleWorker();
    expect(count($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE])))->equals(0);
    $worker->schedule();
    expect(count($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE])))->equals(1);
    $worker->schedule();
    expect(count($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE])))->equals(1);
  }

  public function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $currentDate = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    /** @var Carbon $nextRunDate */
    $nextRunDate = (new MockSimpleWorker())->getNextRunDate();
    $difference = $nextRunDate->diffInDays($currentDate);
    // Subtract days left in the current week
    $difference -= (Carbon::DAYS_PER_WEEK - (int)$currentDate->format('N'));
    expect($difference)->lessOrEquals(7);
    expect($difference)->greaterOrEquals(0);
  }
}
