<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Help;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;

class HelpTest extends \MailPoetTest {

  /** @var Help */
  private $endpoint;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->endpoint = $this->diContainer->get(Help::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItReturnsErrorWhenIdIsMissing() {
    $response = $this->endpoint->cancelTask([]);
    verify($response)->instanceOf('\MailPoet\API\JSON\ErrorResponse');
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Missing mandatory argument `id`.');

    $response = $this->endpoint->rescheduleTask([]);
    verify($response)->instanceOf('\MailPoet\API\JSON\ErrorResponse');
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Missing mandatory argument `id`.');
  }

  public function testItReturnsErrorWhenTaskDoesntExist() {
    $response = $this->endpoint->cancelTask(['id' => 99999]);
    verify($response)->instanceOf('\MailPoet\API\JSON\ErrorResponse');
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Task not found.');

    $response = $this->endpoint->rescheduleTask(['id' => 99999]);
    verify($response)->instanceOf('\MailPoet\API\JSON\ErrorResponse');
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Task not found.');
  }

  public function testItReturnsErrorWhenCancellingCompletedTask() {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, new \DateTime());
    $response = $this->endpoint->cancelTask(['id' => $task->getId()]);
    verify($response)->instanceOf('\MailPoet\API\JSON\ErrorResponse');
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Only scheduled and running tasks can be cancelled');
  }

  public function testItReturnsErrorWhenReschedulingCompletedTask() {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, new \DateTime());
    $response = $this->endpoint->rescheduleTask(['id' => $task->getId()]);
    verify($response)->instanceOf('\MailPoet\API\JSON\ErrorResponse');
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Only cancelled tasks can be rescheduled');
  }

  public function testItCanCancelScheduledTask() {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED, new \DateTime());
    $response = $this->endpoint->cancelTask(['id' => $task->getId()]);
    verify($response)->instanceOf(APIResponse::class);
    verify($response->status)->equals(200);

    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    verify($task)->instanceOf(ScheduledTaskEntity::class);
    if ($task) {
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_CANCELLED);
      verify($task->getCancelledAt())->notNull();
      verify($task->getInProgress())->equals(0);
    }
  }

  public function testItCanCancelRunningTask() {
    $task = (new ScheduledTaskFactory())->create('sending', null, new \DateTime());
    $response = $this->endpoint->cancelTask(['id' => $task->getId()]);
    verify($response)->instanceOf(APIResponse::class);
    verify($response->status)->equals(200);

    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    verify($task)->instanceOf(ScheduledTaskEntity::class);
    if ($task) {
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_CANCELLED);
      verify($task->getCancelledAt())->notNull();
      verify($task->getInProgress())->equals(0);
    }
  }

  public function testItCanRescheduleTask() {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_CANCELLED, new \DateTime());
    $response = $this->endpoint->rescheduleTask(['id' => $task->getId()]);
    verify($response)->instanceOf(APIResponse::class);
    verify($response->status)->equals(200);

    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    verify($task)->instanceOf(ScheduledTaskEntity::class);
    if ($task) {
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
      verify($task->getCancelledAt())->null();
      verify($task->getInProgress())->null();
    }
  }
}
