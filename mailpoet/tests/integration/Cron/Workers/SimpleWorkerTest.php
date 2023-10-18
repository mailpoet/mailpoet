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
      verify($e->getMessage())->equals('Constant TASK_TYPE is not defined on subclass ' . $workerClass);
    }
  }

  public function testItSchedulesTask() {
    verify($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE]))->empty();
    (new MockSimpleWorker())->schedule();
    verify($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE]))->notEmpty();
  }

  public function testItDoesNotScheduleTaskTwice() {
    $worker = new MockSimpleWorker();
    verify(count($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE])))->equals(0);
    $worker->schedule();
    verify(count($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE])))->equals(1);
    $worker->schedule();
    verify(count($this->scheduledTasksRepository->findBy(['type' => MockSimpleWorker::TASK_TYPE])))->equals(1);
  }

  public function testItCalculatesNextRunDateWithinNextWeekBoundaries() {
    $currentDate = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    /** @var Carbon $nextRunDate */
    $nextRunDate = (new MockSimpleWorker())->getNextRunDate();
    $difference = $nextRunDate->diffInDays($currentDate);
    // Subtract days left in the current week
    $difference -= (Carbon::DAYS_PER_WEEK - (int)$currentDate->format('N'));
    verify($difference)->lessThanOrEqual(7);
    verify($difference)->greaterThanOrEqual(0);
  }
}
