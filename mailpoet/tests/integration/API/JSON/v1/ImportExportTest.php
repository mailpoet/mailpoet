<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\ImportExport;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoetVendor\Carbon\Carbon;

class ImportExportTest extends \MailPoetTest {

  /** @var ImportExport */
  private $endpoint;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(ImportExport::class);
    $this->scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskEntity::class, 's')
      ->where('s.type = :type')
      ->setParameter('type', WooCommerceSync::TASK_TYPE)
      ->getQuery()
      ->execute();
  }

  public function testItSchedulesTaskWhenNoneExists() {
    $response = $this->endpoint->setupWooCommerceInitialImport();
    verify($response->status)->equals(200);
    $task = $this->scheduledTasksRepository->findOneBy(['type' => WooCommerceSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $now = time();
    $scheduledAt = new Carbon($task->getScheduledAt());
    verify($scheduledAt->timestamp)->greaterThanOrEqual($now - 1);
    verify($scheduledAt->timestamp)->lessThanOrEqual($now + 1);
  }

  public function testItReschedulesScheduledTaskToNow() {
    $originalSchedule = Carbon::createFromTimestamp((int)current_time('timestamp') + 3000);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, $originalSchedule);
    $this->entityManager->clear();
    $this->endpoint->setupWooCommerceInitialImport();
    $task = $this->scheduledTasksRepository->findOneBy(['type' => WooCommerceSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    $now = time();
    $scheduledAt = new Carbon($task->getScheduledAt());
    verify($scheduledAt->timestamp)->greaterThanOrEqual($now - 1);
    verify($scheduledAt->timestamp)->lessThanOrEqual($now + 1);
    $taskCount = $this->scheduledTasksRepository->countBy(['type' => WooCommerceSync::TASK_TYPE]);
    verify($taskCount)->equals(1);
  }

  public function testItDoesNothingForRunningTask() {
    $this->createTask(WooCommerceSync::TASK_TYPE, null);
    $this->endpoint->setupWooCommerceInitialImport();
    $task = $this->scheduledTasksRepository->findOneBy(['type' => WooCommerceSync::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getStatus())->equals(null);
    $taskCount = $this->scheduledTasksRepository->countBy(['type' => WooCommerceSync::TASK_TYPE]);
    verify($taskCount)->equals(1);
  }

  public function testItIgnoresCompletedAndPausedTasks() {
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED);
    $this->createTask(WooCommerceSync::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    $this->endpoint->setupWooCommerceInitialImport();
    $taskCount = $this->scheduledTasksRepository->countBy(['type' => WooCommerceSync::TASK_TYPE]);
    verify($taskCount)->equals(3);
  }

  public function testAddSegmentCreatesNewSegmentEvenWithExistingId() {
    $existingSegment = (new SegmentFactory())->withName('Existing list')->withDescription('Existing description')->create();

    $response = $this->endpoint->addSegment([
      'id' => $existingSegment->getId(),
      'name' => 'Imported list',
      'description' => 'Imported description',
    ]);

    verify($response->status)->equals(200);
    verify($response->data['id'])->notEquals($existingSegment->getId());
    verify($response->data['name'])->equals('Imported list');
    $this->entityManager->refresh($existingSegment);
    verify($existingSegment->getName())->equals('Existing list');
    verify($existingSegment->getDescription())->equals('Existing description');
  }

  public function testAddSegmentIgnoresPublicDescription() {
    $response = $this->endpoint->addSegment([
      'name' => 'Imported list',
      'description' => 'Imported description',
      'public_description' => 'Public description',
    ]);

    verify($response->status)->equals(200);
    $segment = $this->entityManager->find(SegmentEntity::class, $response->data['id']);
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    verify($segment->getPublicDescription())->equals('');
  }

  private function createTask($type, $status = null, $scheduledAt = null) {
    if (!$scheduledAt) {
      Carbon::now()->millisecond(0);
    }
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus($status);
    $task->setScheduledAt($scheduledAt);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }
}
