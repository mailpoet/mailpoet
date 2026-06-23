<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use Throwable;

class ScheduledTaskSubscriberMoverTest extends \MailPoetTest {
  private ScheduledTaskSubscriberMover $mover;

  private ScheduledTaskFactory $scheduledTaskFactory;

  private string $queueTable;

  private string $logTable;

  public function _before() {
    parent::_before();
    $this->mover = $this->diContainer->get(ScheduledTaskSubscriberMover::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->queueTable = $this->entityManager->getClassMetadata(ScheduledTaskQueuedSubscriberEntity::class)->getTableName();
    $this->logTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
  }

  public function testItMovesProcessedQueueRowsToLog(): void {
    $task = $this->createTask();
    $subscriber1 = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    $createdAt1 = '2024-01-02 03:04:05';
    $createdAt2 = '2024-01-02 03:05:06';

    $this->createQueuedSubscriber($task, $subscriber1, $createdAt1);
    $this->createQueuedSubscriber($task, $subscriber2, $createdAt2);

    $this->mover->moveProcessedToLog($task, [
      (int)$subscriber2->getId(),
      (int)$subscriber1->getId(),
    ]);

    $logRow1 = $this->getLogRow($task, $subscriber1);
    $logRow2 = $this->getLogRow($task, $subscriber2);
    $this->assertNotNull($logRow1);
    $this->assertNotNull($logRow2);
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, (int)$logRow1['processed']);
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_OK, (int)$logRow1['failed']);
    $this->assertNull($logRow1['error']);
    $this->assertSame($createdAt1, $logRow1['created_at']);
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, (int)$logRow2['processed']);
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_OK, (int)$logRow2['failed']);
    $this->assertNull($logRow2['error']);
    $this->assertSame($createdAt2, $logRow2['created_at']);
    $this->assertNull($this->getQueueRow($task, $subscriber1));
    $this->assertNull($this->getQueueRow($task, $subscriber2));
  }

  public function testItMovesFailedQueueRowToLog(): void {
    $task = $this->createTask();
    $subscriber = $this->createSubscriber();
    $createdAt = '2024-02-03 04:05:06';
    $error = 'SMTP rejected recipient';

    $this->createQueuedSubscriber($task, $subscriber, $createdAt);

    $this->mover->moveFailedToLog($task, (int)$subscriber->getId(), $error);

    $logRow = $this->getLogRow($task, $subscriber);
    $this->assertNotNull($logRow);
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, (int)$logRow['processed']);
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED, (int)$logRow['failed']);
    $this->assertSame($error, $logRow['error']);
    $this->assertSame($createdAt, $logRow['created_at']);
    $this->assertNull($this->getQueueRow($task, $subscriber));
  }

  public function testItKeepsQueueRowsWhenLogInsertFails(): void {
    $task = $this->createTask();
    $conflictingSubscriber = $this->createSubscriber();
    $subscriber = $this->createSubscriber();
    $createdAt = '2024-03-04 05:06:07';

    $this->createQueuedSubscriber($task, $conflictingSubscriber, $createdAt);
    $this->createQueuedSubscriber($task, $subscriber, $createdAt);
    $this->createLogSubscriber($task, $conflictingSubscriber, $createdAt);

    $moveFailed = false;
    try {
      $this->mover->moveProcessedToLog($task, [
        (int)$conflictingSubscriber->getId(),
        (int)$subscriber->getId(),
      ]);
    } catch (Throwable $e) {
      $moveFailed = true;
    }

    $this->assertTrue($moveFailed, 'Expected the duplicate log row to fail the move.');
    $this->assertNotNull($this->getQueueRow($task, $conflictingSubscriber));
    $this->assertNotNull($this->getQueueRow($task, $subscriber));
    $this->assertNotNull($this->getLogRow($task, $conflictingSubscriber));
    $this->assertNull($this->getLogRow($task, $subscriber));
  }

  public function testItDoesNotCreateLogRowsForSubscribersMissingFromTheQueue(): void {
    $task = $this->createTask();
    $subscriber = $this->createSubscriber();

    $this->mover->moveProcessedToLog($task, [(int)$subscriber->getId()]);

    $this->assertNull($this->getLogRow($task, $subscriber));
    $this->assertNull($this->getQueueRow($task, $subscriber));
  }

  public function testItMovesLogRowBackToQueue(): void {
    $task = $this->createTask();
    $subscriber = $this->createSubscriber();

    $this->createLogSubscriber(
      $task,
      $subscriber,
      '2024-04-05 06:07:08',
      ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED,
      'Temporary failure'
    );

    $this->mover->moveBackToQueue($task, (int)$subscriber->getId());

    $queueRow = $this->getQueueRow($task, $subscriber);
    $this->assertNotNull($queueRow);
    $this->assertNotNull($queueRow['created_at']);
    $this->assertNull($this->getLogRow($task, $subscriber));
  }

  private function createTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, Carbon::now()->subDay());
  }

  private function createSubscriber(): SubscriberEntity {
    return (new SubscriberFactory())->create();
  }

  private function createQueuedSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber, string $createdAt): void {
    $this->connection->executeStatement(
      "INSERT INTO $this->queueTable
       (`task_id`, `subscriber_id`, `created_at`)
       VALUES (:taskId, :subscriberId, :createdAt)",
      [
        'taskId' => $task->getId(),
        'subscriberId' => $subscriber->getId(),
        'createdAt' => $createdAt,
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
        'createdAt' => ParameterType::STRING,
      ]
    );
  }

  private function createLogSubscriber(
    ScheduledTaskEntity $task,
    SubscriberEntity $subscriber,
    string $createdAt,
    int $failed = ScheduledTaskSubscriberEntity::FAIL_STATUS_OK,
    ?string $error = null
  ): void {
    $this->connection->executeStatement(
      "INSERT INTO $this->logTable
       (`task_id`, `subscriber_id`, `processed`, `failed`, `error`, `created_at`, `updated_at`)
       VALUES (:taskId, :subscriberId, :processed, :failed, :error, :createdAt, :updatedAt)",
      [
        'taskId' => $task->getId(),
        'subscriberId' => $subscriber->getId(),
        'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
        'failed' => $failed,
        'error' => $error,
        'createdAt' => $createdAt,
        'updatedAt' => $createdAt,
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
        'processed' => ParameterType::INTEGER,
        'failed' => ParameterType::INTEGER,
        'error' => ParameterType::STRING,
        'createdAt' => ParameterType::STRING,
        'updatedAt' => ParameterType::STRING,
      ]
    );
  }

  /** @return array<string, mixed>|null */
  private function getQueueRow(ScheduledTaskEntity $task, SubscriberEntity $subscriber): ?array {
    $row = $this->connection->executeQuery(
      "SELECT *
       FROM $this->queueTable
       WHERE `task_id` = :taskId
       AND `subscriber_id` = :subscriberId",
      [
        'taskId' => $task->getId(),
        'subscriberId' => $subscriber->getId(),
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
      ]
    )->fetchAssociative();

    return $row ?: null;
  }

  /**
   * @return array<string, string|null>|null
   */
  private function getLogRow(ScheduledTaskEntity $task, SubscriberEntity $subscriber): ?array {
    $row = $this->connection->executeQuery(
      "SELECT *
       FROM $this->logTable
       WHERE `task_id` = :taskId
       AND `subscriber_id` = :subscriberId",
      [
        'taskId' => $task->getId(),
        'subscriberId' => $subscriber->getId(),
      ],
      [
        'taskId' => ParameterType::INTEGER,
        'subscriberId' => ParameterType::INTEGER,
      ]
    )->fetchAssociative();
    if (!$row) {
      return null;
    }
    /** @var array<string, string|null> $row */
    return $row;
  }
}
