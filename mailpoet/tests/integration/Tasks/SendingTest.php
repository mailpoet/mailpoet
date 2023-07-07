<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\Subscribers;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SendingTest extends \MailPoetTest {
  public $sending;
  public $queue;
  public $task;
  public $newsletter;

  /** @var ScheduledTasksRepository */
  private $scheduledTaskRepository;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var SubscriberEntity */
  private $subscriber1;

  /** SubscriberEntity */
  private $subscriber2;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  public function _before() {
    parent::_before();
    $this->newsletterFactory = new NewsletterFactory();
    $this->newsletter = $this->newsletterFactory->create();
    $this->subscriberFactory = new SubscriberFactory();
    $this->subscriber1 = $this->subscriberFactory->withEmail('subscriber1@test.com')->create();
    $this->subscriber2 = $this->subscriberFactory->withEmail('subscriber2@test.com')->create();
    $this->task = $this->createNewScheduledTask();
    $this->queue = $this->createNewSendingQueue([
      'newsletter' => $this->newsletter,
      'task' => $this->task,
    ]);
    $this->sending = $this->createNewSendingTask([
      'status' => null,
      'task' => $this->task,
      'queue' => $this->queue,
    ]);
    $this->scheduledTaskRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
  }

  public function testItCanBeConstructed() {
    $sending = SendingTask::create();
    expect_that($sending instanceof SendingTask);
    expect_that($sending->queue() instanceof SendingQueue);
    expect_that($sending->task() instanceof ScheduledTask);
    expect_that($sending->taskSubscribers() instanceof Subscribers);
  }

  public function testItCanBeConstructedOnlyWithAProperTaskType() {
    $this->task->type = 'wrong_type';
    try {
      $sending = SendingTask::create($this->task, $this->queue);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      // No exception handling necessary
    }
  }

  public function testItCanCreateManyFromTasks() {
    $sendings = SendingTask::createManyFromTasks([$this->task]);
    expect($sendings)->notEmpty();
    $queue = $sendings[0]->queue();
    expect($queue->taskId)->equals($this->task->id);
  }

  public function testItDeletesInvalidTasksWhenCreatingManyFromTasks() {
    $this->queue->delete();
    $sendings = SendingTask::createManyFromTasks([$this->task]);
    expect($sendings)->isEmpty();
    $task = ScheduledTask::findOne($this->task->id);
    $this->assertInstanceOf(ScheduledTask::class, $task);
    expect($task->status)->equals(ScheduledTask::STATUS_INVALID);
  }

  public function testItCanBeCreatedFromScheduledTask() {
    $sending = SendingTask::createFromScheduledTask($this->task);
    $queue = $sending->queue();
    expect($queue->taskId)->equals($this->task->id);
  }

  public function testItCanBeCreatedFromQueue() {
    $sending = SendingTask::createFromQueue($this->queue);
    $task = $sending->task();
    expect($task->id)->equals($this->queue->task_id);
  }

  public function testItCanBeInitializedByNewsletterId() {
    $sending = SendingTask::getByNewsletterId($this->newsletter->getId());
    $queue = $sending->queue();
    $task = $sending->task();
    expect($task->id)->equals($queue->taskId);
  }

  public function testItCanBeConvertedToArray() {
    $sending = $this->sending->asArray();
    expect($sending['id'])->equals($this->queue->id);
    expect($sending['task_id'])->equals($this->task->id);
  }

  public function testItSavesDataForUnderlyingModels() {
    $newsletterRenderedSubject = 'Abc';
    $status = ScheduledTask::STATUS_PAUSED;
    $this->sending->status = $status;
    $this->sending->newsletter_rendered_subject = $newsletterRenderedSubject;
    $this->sending->save();
    $task = ScheduledTask::findOne($this->task->id);
    $queue = SendingQueue::findOne($this->queue->id);
    $this->assertInstanceOf(ScheduledTask::class, $task);
    $this->assertInstanceOf(SendingQueue::class, $queue);
    expect($task->status)->equals($status);
    expect($queue->newsletterRenderedSubject)->equals($newsletterRenderedSubject);
  }

  public function testItDeletesUnderlyingModels() {
    $this->sending->delete();
    expect(ScheduledTask::findOne($this->task->id))->equals(false);
    expect(SendingQueue::findOne($this->queue->id))->equals(false);
    expect($this->scheduledTaskSubscribersRepository->findBy(['task' => $this->task->id]))->isEmpty();
  }

  public function testItGetsSubscribers() {
    expect($this->sending->getSubscribers())->same([(string)$this->subscriber1->getId(), (string)$this->subscriber2->getId()]);
  }

  public function testItGetsOnlyProcessedSubscribers() {
    $this->sending->updateProcessedSubscribers([$this->subscriber1->getId()]);

    expect($this->sending->getSubscribers(true))->same([(string)$this->subscriber1->getId()]);
  }

  public function testItGetsOnlyUnprocessedSubscribers() {
    $this->sending->updateProcessedSubscribers([$this->subscriber1->getId()]);

    expect($this->sending->getSubscribers(false))->same([(string)$this->subscriber2->getId()]);
  }

  public function testItSetsSubscribers() {
    $subscriber3 = $this->subscriberFactory->withEmail('subscriber3@test.com')->create();
    $subscriber4 = $this->subscriberFactory->withEmail('subscriber4@test.com')->create();
    $subscriber5 = $this->subscriberFactory->withEmail('subscriber5@test.com')->create();

    $subscriberIds = [$subscriber3->getId(), $subscriber4->getId(), $subscriber5->getId()];
    $this->sending->setSubscribers($subscriberIds);

    expect($this->sending->getSubscribers())->equals($subscriberIds);
    expect($this->sending->count_total)->equals(count($subscriberIds));
    expect($this->sending->count_processed)->equals(0);
    expect($this->sending->count_to_process)->equals(3);
  }

  public function testItRemovesSubscribers() {
    $subscriberIds = [$this->subscriber2->getId()];
    $this->sending->removeSubscribers($subscriberIds);
    expect($this->sending->getSubscribers())->equals([$this->subscriber1->getId()]);
    expect($this->sending->count_total)->equals(1);
    expect($this->sending->status)->null();
  }

  public function testItRemovesSubscribersShouldMarkTaskAsComplete() {
    $subscriberIds = [$this->subscriber1->getId(), $this->subscriber2->getId()];
    $originalProcessedAt = $this->sending->processed_at;

    $this->sending->removeSubscribers($subscriberIds);

    expect($this->sending->getSubscribers())->isEmpty();
    expect($this->sending->count_total)->equals(0);
    expect($this->sending->status)->same(ScheduledTaskEntity::STATUS_COMPLETED);
    expect($this->sending->processed_at)->notSame($originalProcessedAt);
  }

  public function testItUpdatesProcessedSubscribers() {
    $taskSubscriber1 = $this->getTaskSubscriber($this->task->id, $this->subscriber1->getId());
    $subscriberId2 = $this->subscriber2->getId();
    $taskSubscriber2 = $this->getTaskSubscriber($this->task->id, $subscriberId2);
    expect($taskSubscriber2->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);

    expect($this->sending->count_to_process)->equals(2);
    expect($this->sending->count_processed)->equals(0);
    $return = $this->sending->updateProcessedSubscribers([$subscriberId2]);
    expect($return)->true();
    expect($this->sending->count_to_process)->equals(1);
    expect($this->sending->count_processed)->equals(1);
    expect($this->sending->status)->equals(null);

    $taskSubscriber2 = $this->getTaskSubscriber($this->task->id, $subscriberId2);
    expect($taskSubscriber2->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    expect($taskSubscriber1->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);

    $this->sending->updateProcessedSubscribers([$this->subscriber1->getId()]);
    expect($this->sending->status)->equals(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testItGetsScheduledQueues() {
    $this->sending->status = ScheduledTask::STATUS_SCHEDULED;
    $this->sending->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    $this->sending->save();
    $tasks = $this->scheduledTaskRepository->findScheduledSendingTasks();
    expect($tasks)->notEmpty();
    foreach ($tasks as $task) {
      expect($task)->isInstanceOf(ScheduledTaskEntity::class);
    }

    // if task exists but sending queue is missing, results should not contain empty (false) values
    $this->queue->delete();
    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks();
    expect($tasks)->isEmpty();
  }

  public function testItGetsBatchOfScheduledQueues() {
    $amount = 5;
    for ($i = 0; $i < $amount + 3; $i += 1) {
      $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    }
    expect($this->scheduledTaskRepository->findScheduledSendingTasks($amount))->count($amount);
  }

  public function testItDoesNotGetPaused() {
    $this->createNewSendingTask(['status' => ScheduledTask::STATUS_PAUSED]);
    expect($this->scheduledTaskRepository->findScheduledSendingTasks())->count(0);
  }

  public function testItGetsRunningQueues() {
    $this->sending->status = null;
    $this->sending->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    $this->sending->save();
    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks();
    expect($tasks)->notEmpty();
    foreach ($tasks as $task) {
      expect($task)->isInstanceOf(ScheduledTaskEntity::class);
    }

    // if task exists but sending queue is missing, results should not contain empty (false) values
    $this->queue->delete();
    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks();
    expect($tasks)->isEmpty();
  }

  public function testItGetsBatchOfRunningQueues() {
    $amount = 5;
    for ($i = 0; $i < $amount + 3; $i += 1) {
      $this->createNewSendingTask(['status' => null]);
    }
    expect($this->scheduledTaskRepository->findRunningSendingTasks($amount))->count($amount);
  }

  public function testItGetsBatchOfRunningQueuesSortedByUpdatedTime() {
    $sending1 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending1->updatedAt = '2017-05-04 14:00:00';
    $sending1->save();
    $sending2 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending2->updatedAt = '2017-05-04 16:00:00';
    $sending2->save();
    $sending3 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending3->updatedAt = '2017-05-04 15:00:00';
    $sending3->save();

    $tasks = $this->scheduledTaskRepository->findScheduledSendingTasks(3);
    expect($tasks[0]->getId())->equals($sending1->taskId);
    expect($tasks[1]->getId())->equals($sending3->taskId);
    expect($tasks[2]->getId())->equals($sending2->taskId);
  }

  public function testItGetsBatchOfScheduledQueuesSortedByUpdatedTime() {
    $sending1 = $this->createNewSendingTask(['status' => null]);
    $sending1->updatedAt = '2017-05-04 14:00:00';
    $sending1->save();
    $sending2 = $this->createNewSendingTask(['status' => null]);
    $sending2->updatedAt = '2017-05-04 16:00:00';
    $sending2->save();
    $sending3 = $this->createNewSendingTask(['status' => null]);
    $sending3->updatedAt = '2017-05-04 15:00:00';
    $sending3->save();

    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks(3);
    expect($tasks[0]->getId())->equals($sending1->taskId);
    expect($tasks[1]->getId())->equals($sending3->taskId);
    expect($tasks[2]->getId())->equals($sending2->taskId);
  }

  public function createNewScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = SendingTask::TASK_TYPE;
    return $task->save();
  }

  public function createNewSendingQueue($args = []) {
    $newsletter = isset($args['newsletter']) ? $args['newsletter'] : $this->newsletterFactory->create();
    $task = isset($args['task']) ? $args['task'] : $this->createNewScheduledTask();

    $queue = SendingQueue::create();
    $queue->newsletterId = $newsletter->getId();
    $queue->taskId = $task->id;
    return $queue->save();
  }

  public function createNewSendingTask($args = []) {
    $task = isset($args['task']) ? $args['task'] : $this->createNewScheduledTask();
    $queue = isset($args['queue']) ? $args['queue'] : $this->createNewSendingQueue(['task' => $task]);
    $status = isset($args['status']) ? $args['status'] : null;

    $sending = SendingTask::create($task, $queue);
    $sending->setSubscribers([$this->subscriber1->getId(), $this->subscriber2->getId()]);
    $sending->status = $status;
    $sending->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    return $sending->save();
  }

  private function getTaskSubscriber($taskId, $subscriberId): ScheduledTaskSubscriberEntity {
    $scheduledTaskSubscriber = $this->scheduledTaskSubscribersRepository->findOneBy(['task' => $taskId, 'subscriber' => $subscriberId]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $scheduledTaskSubscriber);
    $this->scheduledTaskSubscribersRepository->refresh($scheduledTaskSubscriber);

    return $scheduledTaskSubscriber;
  }
}
