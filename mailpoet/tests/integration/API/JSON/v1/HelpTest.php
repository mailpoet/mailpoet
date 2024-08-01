<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Help;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Util\DataInconsistency\DataInconsistencyController;
use MailPoetVendor\Carbon\Carbon;

class HelpTest extends \MailPoetTest {

  private Help $endpoint;
  private ScheduledTasksRepository $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->endpoint = $this->diContainer->get(Help::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItReturnsErrorWhenIdIsMissing(): void {
    /** @var ErrorResponse $response */
    $response = $this->endpoint->cancelTask([]);
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Invalid or missing parameter `id`.');

    /** @var ErrorResponse $response */
    $response = $this->endpoint->rescheduleTask([]);
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Invalid or missing parameter `id`.');
  }

  public function testItReturnsErrorWhenTaskDoesntExist(): void {
    /** @var ErrorResponse $response */
    $response = $this->endpoint->cancelTask(['id' => 99999]);
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->status)->equals(404);
    verify($response->errors[0]['message'])->equals('Task not found.');

    /** @var ErrorResponse $response */
    $response = $this->endpoint->rescheduleTask(['id' => 99999]);
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->status)->equals(404);
    verify($response->errors[0]['message'])->equals('Task not found.');
  }

  public function testItReturnsErrorWhenCancellingCompletedTask(): void {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, new \DateTime());
    /** @var ErrorResponse $response */
    $response = $this->endpoint->cancelTask(['id' => $task->getId()]);
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Only scheduled and running tasks can be cancelled');
  }

  public function testItReturnsErrorWhenReschedulingCompletedTask(): void {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_COMPLETED, new \DateTime());
    /** @var ErrorResponse $response */
    $response = $this->endpoint->rescheduleTask(['id' => $task->getId()]);
    verify($response)->instanceOf(ErrorResponse::class);
    verify($response->status)->equals(400);
    verify($response->errors[0]['message'])->equals('Only cancelled tasks can be rescheduled');
  }

  public function testItCanCancelScheduledTask(): void {
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

  public function testItCanCancelRunningTask(): void {
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

  public function testItCanRescheduleTaskInFuture(): void {
    $futureDate = Carbon::now()->addDay();
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_CANCELLED, $futureDate);
    $response = $this->endpoint->rescheduleTask(['id' => $task->getId()]);
    verify($response)->instanceOf(APIResponse::class);
    verify($response->status)->equals(200);

    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    verify($task)->instanceOf(ScheduledTaskEntity::class);
    if ($task) {
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
      verify($task->getCancelledAt())->null();
    }
  }

  public function testItCanRescheduleTaskInProgress(): void {
    $pastDate = Carbon::now()->subDay();
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_CANCELLED, $pastDate);
    $response = $this->endpoint->rescheduleTask(['id' => $task->getId()]);
    verify($response)->instanceOf(APIResponse::class);
    verify($response->status)->equals(200);

    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    verify($task)->instanceOf(ScheduledTaskEntity::class);
    if ($task) {
      verify($task->getStatus())->equals(null); // task is running
      verify($task->getCancelledAt())->null();
    }
  }

  public function testItFixesInconsistentData(): void {
    $task = (new ScheduledTaskFactory())->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->detach($task);

    $response = $this->endpoint->fixInconsistentData(['inconsistency' => DataInconsistencyController::ORPHANED_SENDING_TASKS]);
    $task = $this->scheduledTasksRepository->findOneById($task->getId());
    verify($task)->null();
    verify($response->data['total'] ?? null)->equals(0);
    verify($response->data[DataInconsistencyController::ORPHANED_SENDING_TASKS] ?? null)->equals(0);
    verify($response->data[DataInconsistencyController::ORPHANED_NEWSLETTER_POSTS] ?? null)->equals(0);
    verify($response->data[DataInconsistencyController::ORPHANED_SUBSCRIPTIONS] ?? null)->equals(0);
    verify($response->data[DataInconsistencyController::ORPHANED_SENDING_TASK_SUBSCRIBERS] ?? null)->equals(0);
    verify($response->data[DataInconsistencyController::SENDING_QUEUE_WITHOUT_NEWSLETTER] ?? null)->equals(0);
    verify($response->data[DataInconsistencyController::ORPHANED_LINKS] ?? null)->equals(0);
  }
}
