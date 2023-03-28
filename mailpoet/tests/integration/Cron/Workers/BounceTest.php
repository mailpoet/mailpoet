<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\Bounce;
use MailPoet\Cron\Workers\Bounce\BounceTestMockAPI as MockAPI;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsBouncesRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once('BounceTestMockAPI.php');

class BounceTest extends \MailPoetTest {

  /** @var Bounce */
  private $worker;

  /** @var string[] */
  private $emails;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  public function _before() {
    parent::_before();
    $this->emails = [
      'soft_bounce@example.com',
      'hard_bounce@example.com',
      'good_address@example.com',
      'unconfirmed@example.com',
    ];
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();

    foreach ($this->emails as $email) {
      $subscriber = new SubscriberEntity();
      $subscriber->setStatus(strpos($email, 'unconfirmed') !== false ? SubscriberEntity::STATUS_UNCONFIRMED : SubscriberEntity::STATUS_SUBSCRIBED);
      $subscriber->setEmail($email);
      $this->subscribersRepository->persist($subscriber);
    }

    $this->worker = new Bounce(
      $this->diContainer->get(SettingsController::class),
      $this->subscribersRepository,
      $this->diContainer->get(SendingQueuesRepository::class),
      $this->diContainer->get(StatisticsBouncesRepository::class),
      $this->diContainer->get(ScheduledTaskSubscribersRepository::class),
      $this->diContainer->get(Bridge::class)
    );

    $this->worker->api = new MockAPI();
    $this->subscribersRepository->flush();
    $this->entityManager->clear();
  }

  public function testItDefinesConstants() {
    expect(Bounce::BATCH_SIZE)->equals(100);
  }

  public function testItCanInitializeBridgeAPI() {
    $this->setMailPoetSendingMethod();
    $worker = new Bounce(
      $this->diContainer->get(SettingsController::class),
      $this->subscribersRepository,
      $this->diContainer->get(SendingQueuesRepository::class),
      $this->diContainer->get(StatisticsBouncesRepository::class),
      $this->diContainer->get(ScheduledTaskSubscribersRepository::class),
      $this->diContainer->get(Bridge::class)
    );
    $worker->init();
    expect($worker->api instanceof API)->true();
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  public function testItDeletesAllSubscribersIfThereAreNoSubscribersToProcessWhenPreparingTask() {
    // 1st run - subscribers will be processed
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($this->scheduledTaskSubscribersRepository->findBy(['task' => $task]))->notEmpty();

    // 2nd run - nothing more to process, ScheduledTaskSubscriber will be cleaned up
    $this->truncateEntity(SubscriberEntity::class);
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($this->scheduledTaskSubscribersRepository->findBy(['task' => $task]))->isEmpty();
  }

  public function testItPreparesTask() {
    $task = $this->createScheduledTask();
    expect($this->scheduledTaskSubscribersRepository->countBy([
      'task' => $task,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
    ]))->equals(0);
    $result = $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($this->emails)->count($this->scheduledTaskSubscribersRepository->countBy([]));
    expect($result)->true();
    expect($this->scheduledTaskSubscribersRepository->countBy([
      'task' => $task,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
    ]))->equals(count($this->emails));
  }

  public function testItDeletesAllSubscribersIfThereAreNoSubscribersToProcessWhenProcessingTask() {
    // prepare subscribers
    $task = $this->createScheduledTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($this->scheduledTaskSubscribersRepository->findBy(['task' => $task]))->notEmpty();

    // process - no subscribers found, ScheduledTaskSubscriber will be cleaned up
    $task = $this->createScheduledTask();
    $this->worker->processTaskStrategy($task, microtime(true));
    expect($this->scheduledTaskSubscribersRepository->findBy(['task' => $task]))->isEmpty();
  }

  public function testItProcessesTask() {
    $task = $this->createRunningTask();
    $this->worker->prepareTaskStrategy($task, microtime(true));
    expect($this->scheduledTaskSubscribersRepository->countBy([
      'task' => $task,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
    ]))->notEmpty();

    $this->worker->processTaskStrategy($task, microtime(true));
    expect($this->scheduledTaskSubscribersRepository->countBy([
      'task' => $task,
      'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
    ]))->notEmpty();
  }

  public function testItSetsSubscriberStatusAsBounced() {
    $task = $this->createRunningTask();
    $this->worker->processEmails($task, $this->emails);

    $subscribers = $this->subscribersRepository->findAll();

    expect($subscribers[0]->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscribers[1]->getStatus())->equals(SubscriberEntity::STATUS_BOUNCED);
    expect($subscribers[2]->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscribers[3]->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItCreatesStatistics() {
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'hard_bounce@example.com']);
    // create old data that shouldn't be picked by the code
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $oldNewsletter = $this->createNewsletter();
    $oldSendingTask = $this->createSendingTask();
    $oldSendingTask->setUpdatedAt(Carbon::now()->subDays(5));
    $this->createSendingQueue($oldNewsletter, $oldSendingTask);
    $this->createScheduledTaskSubscriber($oldSendingTask, $subscriber);
    // create previous bounce task
    $previousBounceTask = $this->createRunningTask();
    $previousBounceTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $previousBounceTask->setCreatedAt(Carbon::now()->subDays(6));
    $previousBounceTask->setScheduledAt(Carbon::now()->subDays(4));
    $previousBounceTask->setUpdatedAt(Carbon::now()->subDays(4));
    $this->entityManager->persist($previousBounceTask);
    $this->entityManager->flush();
    // create data that should be used for the current bounce task run
    $newsletter = $this->createNewsletter();
    $sendingTask = $this->createSendingTask() ;
    $sendingTask->setCreatedAt(Carbon::now()->subDays(3));
    $sendingTask->setUpdatedAt(Carbon::now()->subDays(3));
    $this->createSendingQueue($newsletter, $sendingTask);
    $this->createScheduledTaskSubscriber($sendingTask, $subscriber);
    // flush
    $this->entityManager->flush();
    $this->entityManager->clear();
    // run the code
    $this->worker->processEmails($this->createRunningTask(), $this->emails);
    // test it
    $statisticsRepository = $this->diContainer->get(StatisticsBouncesRepository::class);
    $statistics = $statisticsRepository->findAll();
    expect($statistics)->count(1);
  }

  private function setMailPoetSendingMethod() {
    $settings = SettingsController::getInstance();
    $settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
  }

  private function createScheduledTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create(
      'bounce',
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function createRunningTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create(
      'bounce',
      null,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    return $newsletter;
  }

  private function createSendingQueue(NewsletterEntity $newsletter, ScheduledTaskEntity $task): SendingQueueEntity {
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    return $queue;
  }

  private function createSendingTask(): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType('sending');
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->persist($task);
    return $task;
  }

  private function createScheduledTaskSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber) {
    $entity = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($entity);
    return $entity;
  }
}
