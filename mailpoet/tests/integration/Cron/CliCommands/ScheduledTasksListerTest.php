<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\CliCommands;

use InvalidArgumentException;
use MailPoet\Cron\CliCommands\ScheduledTasksLister;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class ScheduledTasksListerTest extends \MailPoetTest {
  /** @var ScheduledTasksLister */
  private $lister;

  /** @var ScheduledTaskFactory */
  private $taskFactory;

  public function _before() {
    parent::_before();
    $this->lister = $this->diContainer->get(ScheduledTasksLister::class);
    $this->taskFactory = new ScheduledTaskFactory();
  }

  public function testItListsScheduledAndRunningTasksByDefault() {
    $scheduled = $this->createTask('typeA', ScheduledTaskEntity::STATUS_SCHEDULED);
    $running = $this->createTask('typeB', null);
    $this->createTask('typeC', ScheduledTaskEntity::STATUS_COMPLETED);
    $this->createTask('typeD', ScheduledTaskEntity::STATUS_CANCELLED);
    $this->createTask('typeE', ScheduledTaskEntity::STATUS_PAUSED);
    $this->createTask('typeF', ScheduledTaskEntity::STATUS_INVALID);

    $rows = $this->lister->getRows();

    $ids = array_column($rows, 'id');
    verify($ids)->arrayCount(2);
    verify(in_array($scheduled->getId(), $ids, true))->true();
    verify(in_array($running->getId(), $ids, true))->true();
  }

  public function testItSortsNewestFirst() {
    $first = $this->createTask('typeA', ScheduledTaskEntity::STATUS_SCHEDULED);
    $second = $this->createTask('typeB', ScheduledTaskEntity::STATUS_SCHEDULED);
    $third = $this->createTask('typeC', ScheduledTaskEntity::STATUS_SCHEDULED);

    $rows = $this->lister->getRows();

    $ids = array_column($rows, 'id');
    verify($ids)->same([$third->getId(), $second->getId(), $first->getId()]);
  }

  public function testItIncludesCliInTheDefaultActionableSet() {
    $cli = $this->createTask('typeA', ScheduledTaskEntity::STATUS_CLI);
    $this->createTask('typeB', ScheduledTaskEntity::STATUS_COMPLETED);

    $rows = $this->lister->getRows();

    $ids = array_column($rows, 'id');
    verify(in_array($cli->getId(), $ids, true))->true();
  }

  /**
   * @dataProvider dataForSingleStatusFilter
   */
  public function testItFiltersAndRendersBySingleStatus(string $key, string $filterValue, string $expectedStatus) {
    $tasks = $this->createOneTaskPerStatus();

    $rows = $this->lister->getRows($filterValue);

    verify($rows)->arrayCount(1);
    verify($rows[0]['id'])->equals($tasks[$key]->getId());
    verify($rows[0]['status'])->same($expectedStatus);
  }

  public function dataForSingleStatusFilter(): array {
    return [
      'scheduled' => ['scheduled', ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_SCHEDULED],
      'running' => ['running', ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING, ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING],
      'cli' => ['cli', ScheduledTaskEntity::STATUS_CLI, ScheduledTaskEntity::STATUS_CLI],
      'completed' => ['completed', ScheduledTaskEntity::STATUS_COMPLETED, ScheduledTaskEntity::STATUS_COMPLETED],
    ];
  }

  public function testItListsAllStatuses() {
    $this->createTask('typeA', ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->createTask('typeB', null);
    $this->createTask('typeC', ScheduledTaskEntity::STATUS_COMPLETED);
    $this->createTask('typeD', ScheduledTaskEntity::STATUS_CANCELLED);
    $this->createTask('typeE', ScheduledTaskEntity::STATUS_PAUSED);
    $this->createTask('typeF', ScheduledTaskEntity::STATUS_INVALID);
    $this->createTask('typeG', ScheduledTaskEntity::STATUS_CLI);

    $rows = $this->lister->getRows('all');

    verify($rows)->arrayCount(7);
  }

  public function testItFiltersByType() {
    $matching = $this->createTask('sending', ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->createTask('bounce', ScheduledTaskEntity::STATUS_SCHEDULED);

    $rows = $this->lister->getRows(null, 'sending');

    verify($rows)->arrayCount(1);
    verify($rows[0]['id'])->equals($matching->getId());
    verify($rows[0]['type'])->same('sending');
  }

  public function testItRespectsLimit() {
    foreach (range(1, 5) as $i) {
      $this->createTask("type{$i}", ScheduledTaskEntity::STATUS_SCHEDULED);
    }

    $rows = $this->lister->getRows(null, null, 3);

    verify($rows)->arrayCount(3);
  }

  public function testItReturnsTheConfiguredColumns() {
    $this->createTask('typeA', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now());

    $rows = $this->lister->getRows();

    verify(array_keys($rows[0]))->same(ScheduledTasksLister::FIELDS);
  }

  public function testItThrowsOnInvalidStatus() {
    $this->expectException(InvalidArgumentException::class);
    $this->lister->getRows('bogus');
  }

  public function testItThrowsOnInvalidLimit() {
    $this->expectException(InvalidArgumentException::class);
    $this->lister->getRows(null, null, 0);
  }

  /** @return array<string, ScheduledTaskEntity> */
  private function createOneTaskPerStatus(): array {
    return [
      'scheduled' => $this->createTask('typeScheduled', ScheduledTaskEntity::STATUS_SCHEDULED),
      'running' => $this->createTask('typeRunning', null),
      'cli' => $this->createTask('typeCli', ScheduledTaskEntity::STATUS_CLI),
      'completed' => $this->createTask('typeCompleted', ScheduledTaskEntity::STATUS_COMPLETED),
      'cancelled' => $this->createTask('typeCancelled', ScheduledTaskEntity::STATUS_CANCELLED),
      'paused' => $this->createTask('typePaused', ScheduledTaskEntity::STATUS_PAUSED),
      'invalid' => $this->createTask('typeInvalid', ScheduledTaskEntity::STATUS_INVALID),
    ];
  }

  private function createTask(string $type, ?string $status, ?Carbon $scheduledAt = null): ScheduledTaskEntity {
    return $this->taskFactory->create($type, $status, $scheduledAt ?? Carbon::now());
  }
}
