<?php declare(strict_types = 1);

namespace MailPoet\Util\DataInconsistency;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\UnexpectedValueException;

class DataInconsistencyControllerTest extends \MailPoetTest {
  private DataInconsistencyController $controller;

  public function _before(): void {
    $this->controller = $this->diContainer->get(DataInconsistencyController::class);
  }

  public function testItReportsSendingQueuesWithoutTaskSeparatelyFromFixableInconsistencies(): void {
    $task = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    (new SendingQueue())->create($task);
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskEntity::class, 't')
      ->where('t.id = :id')
      ->setParameter('id', $task->getId())
      ->getQuery()
      ->execute();

    $unfixable = $this->controller->getUnfixableDataStatus();
    verify($unfixable[DataInconsistencyController::SENDING_QUEUE_WITHOUT_TASK])->equals(1);

    // it must stay out of the fixable set, which drives the "Fix" actions in the UI
    $fixable = $this->controller->getInconsistentDataStatus();
    verify(isset($fixable[DataInconsistencyController::SENDING_QUEUE_WITHOUT_TASK]))->false();
  }

  public function testItRefusesToFixSendingQueuesWithoutTask(): void {
    $this->expectException(UnexpectedValueException::class);
    $this->controller->fixInconsistentData(DataInconsistencyController::SENDING_QUEUE_WITHOUT_TASK);
  }
}
