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
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsBouncesRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

require_once('BounceTestMockAPI.php');

class BounceTest extends \MailPoetTest {

  /** @var Bounce */
  private $worker;

  /** @var MockAPI */
  private $api;

  /** @var string[] */
  private $emails;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->emails = [
      'soft_bounce@example.com',
      'hard_bounce@example.com',
      'good_address@example.com',
      'unconfirmed@example.com',
    ];
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();

    foreach ($this->emails as $email) {
      $subscriber = new SubscriberEntity();
      $subscriber->setStatus(strpos($email, 'unconfirmed') !== false ? SubscriberEntity::STATUS_UNCONFIRMED : SubscriberEntity::STATUS_SUBSCRIBED);
      $subscriber->setEmail($email);
      $this->subscribersRepository->persist($subscriber);
    }

    $this->worker = $this->createWorker();

    $this->api = new MockAPI();
    $this->worker->api = $this->api;
    $this->subscribersRepository->flush();
    $this->entityManager->clear();
  }

  public function testItDefinesConstants() {
    verify(Bounce::MAX_LOOKBACK_DAYS)->equals(14);
  }

  public function testItCanInitializeBridgeAPI() {
    $this->setMailPoetSendingMethod();
    $worker = $this->createWorker();
    $worker->init();
    verify($worker->api instanceof API)->true();
  }

  public function testItRequiresMailPoetMethodToBeSetUp() {
    verify($this->worker->checkProcessingRequirements())->false();
    $this->setMailPoetSendingMethod();
    verify($this->worker->checkProcessingRequirements())->true();
  }

  public function testItMarksReturnedRecipientsAsBounced() {
    $task = $this->createRunningTask();
    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    verify($completed)->true();

    verify($this->getSubscriberStatus('soft_bounce@example.com'))->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($this->getSubscriberStatus('hard_bounce@example.com'))->equals(SubscriberEntity::STATUS_BOUNCED);
    verify($this->getSubscriberStatus('good_address@example.com'))->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($this->getSubscriberStatus('unconfirmed@example.com'))->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItMarksUnconfirmedRecipientsAsBounced() {
    $this->api->reportPages = [1 => ['unconfirmed@example.com']];
    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    verify($this->getSubscriberStatus('unconfirmed@example.com'))->equals(SubscriberEntity::STATUS_BOUNCED);
  }

  public function testItIgnoresRecipientsThatAreNotSubscribedOrUnconfirmed() {
    $unsubscribed = new SubscriberEntity();
    $unsubscribed->setEmail('unsubscribed@example.com');
    $unsubscribed->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->subscribersRepository->persist($unsubscribed);
    $this->subscribersRepository->flush();

    $this->api->reportPages = [1 => ['unsubscribed@example.com', 'unknown@example.com']];
    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    verify($this->getSubscriberStatus('unsubscribed@example.com'))->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItHandlesPagination() {
    $this->api->reportPages = [
      1 => ['hard_bounce@example.com'],
      2 => ['unconfirmed@example.com'],
    ];
    $task = $this->createRunningTask();
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    verify($completed)->true();
    verify(count($this->api->getBouncesReportCalls))->equals(2);
    verify($this->api->getBouncesReportCalls[0]['page'])->equals(1);
    verify($this->api->getBouncesReportCalls[1]['page'])->equals(2);
    verify($this->getSubscriberStatus('hard_bounce@example.com'))->equals(SubscriberEntity::STATUS_BOUNCED);
    verify($this->getSubscriberStatus('unconfirmed@example.com'))->equals(SubscriberEntity::STATUS_BOUNCED);
  }

  public function testItReturnsFalseWhenReportRequestFails() {
    $this->api->failResponse = true;
    $task = $this->createRunningTask();
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    verify($completed)->false();
    verify($this->getSubscriberStatus('hard_bounce@example.com'))->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItUsesYesterdayAsFromDateOnFirstRun() {
    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    $from = $this->api->getBouncesReportCalls[0]['from'];
    $expected = Carbon::now()->subDay();
    verify(abs($from->getTimestamp() - $expected->getTimestamp()))->lessThan(60);
  }

  public function testItUsesLastReportToSettingAsFromDate() {
    $lastReportTo = Carbon::now()->subDays(2);
    $this->settings->set(Bounce::LAST_REPORT_TO_SETTING_KEY, $lastReportTo->format(\DateTimeInterface::ATOM));

    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    $from = $this->api->getBouncesReportCalls[0]['from'];
    verify(abs($from->getTimestamp() - $lastReportTo->getTimestamp()))->lessThan(60);
  }

  public function testItClampsFromDateToMaxLookback() {
    $this->settings->set(Bounce::LAST_REPORT_TO_SETTING_KEY, Carbon::now()->subDays(30)->format(\DateTimeInterface::ATOM));

    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    $from = $this->api->getBouncesReportCalls[0]['from'];
    $earliestAllowed = Carbon::now()->subDays(Bounce::MAX_LOOKBACK_DAYS);
    verify($from->getTimestamp() >= $earliestAllowed->getTimestamp())->true();
    // Clamped close to the 14-day boundary, not 30 days back.
    verify($from->getTimestamp() - $earliestAllowed->getTimestamp())->lessThan(2 * 3600);
  }

  public function testItStoresTheReportRangeOnTheTaskMeta() {
    $task = $this->createRunningTask();
    $this->worker->processTaskStrategy($task, microtime(true));

    $meta = $task->getMeta();
    $this->assertIsArray($meta);
    verify($meta[Bounce::META_FROM])->notEmpty();
    verify($meta[Bounce::META_TO])->notEmpty();
    // Single page consumed, cursor advanced past it.
    verify($meta[Bounce::META_PAGE])->equals(2);
  }

  public function testItRecordsLastReportToOnCompletion() {
    $task = $this->createRunningTask();
    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    verify($completed)->true();

    $to = $this->api->getBouncesReportCalls[0]['to'];
    $stored = $this->settings->get(Bounce::LAST_REPORT_TO_SETTING_KEY);
    verify($stored)->equals($to->format(\DateTimeInterface::ATOM));
  }

  public function testItResumesFromStoredRangeAndPageAfterTimeout() {
    $from = Carbon::now()->subDay()->millisecond(0);
    $to = Carbon::now()->millisecond(0);
    $task = $this->createRunningTask();
    $task->setMeta([
      Bounce::META_FROM => $from->format(\DateTimeInterface::ATOM),
      Bounce::META_TO => $to->format(\DateTimeInterface::ATOM),
      Bounce::META_PAGE => 2,
    ]);
    $this->entityManager->flush();

    $this->api->reportPages = [
      1 => ['hard_bounce@example.com'],
      2 => ['unconfirmed@example.com'],
    ];
    $this->worker->processTaskStrategy($task, microtime(true));

    // Resumes at the stored page; page 1 is never re-requested.
    verify(count($this->api->getBouncesReportCalls))->equals(1);
    verify($this->api->getBouncesReportCalls[0]['page'])->equals(2);
    verify($this->api->getBouncesReportCalls[0]['from']->getTimestamp())->equals($from->getTimestamp());
    verify($this->api->getBouncesReportCalls[0]['to']->getTimestamp())->equals($to->getTimestamp());
    // Page 1's recipient is skipped, page 2's is processed.
    verify($this->getSubscriberStatus('hard_bounce@example.com'))->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($this->getSubscriberStatus('unconfirmed@example.com'))->equals(SubscriberEntity::STATUS_BOUNCED);
  }

  public function testItKeepsTheSameFrozenRangeOnTransientFailureRetry() {
    $this->api->failResponse = true;
    $task = $this->createRunningTask();
    verify($this->worker->processTaskStrategy($task, microtime(true)))->false();
    $firstTo = $this->api->getBouncesReportCalls[0]['to'];

    // Next tick: the range is read back from meta, not recomputed, so `to` does
    // not drift forward.
    $this->api->failResponse = false;
    $this->worker->processTaskStrategy($task, microtime(true));
    $secondTo = $this->api->getBouncesReportCalls[1]['to'];

    verify($secondTo->getTimestamp())->equals($firstTo->getTimestamp());
    verify($this->api->getBouncesReportCalls[1]['page'])->equals(1);
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
    $previousBounceTask->setProcessedAt(Carbon::now()->subDays(4));
    $this->entityManager->persist($previousBounceTask);
    $this->entityManager->flush();
    // create data that should be used for the current bounce task run
    $newsletter = $this->createNewsletter();
    $sendingTask = $this->createSendingTask();
    $sendingTask->setCreatedAt(Carbon::now()->subDays(3));
    $sendingTask->setUpdatedAt(Carbon::now()->subDays(3));
    $this->createSendingQueue($newsletter, $sendingTask);
    $this->createScheduledTaskSubscriber($sendingTask, $subscriber);
    // flush
    $this->entityManager->flush();
    $this->entityManager->clear();
    // run the code
    $this->worker->processRecipients($this->createRunningTask(), ['hard_bounce@example.com']);
    // test it
    $statisticsRepository = $this->diContainer->get(StatisticsBouncesRepository::class);
    $statistics = $statisticsRepository->findAll();
    verify($statistics)->arrayCount(1);
  }

  private function createWorker(): Bounce {
    return new Bounce(
      $this->diContainer->get(SettingsController::class),
      $this->subscribersRepository,
      $this->diContainer->get(SendingQueuesRepository::class),
      $this->diContainer->get(StatisticsBouncesRepository::class),
      $this->diContainer->get(Bridge::class)
    );
  }

  private function getSubscriberStatus(string $email): string {
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    return (string)$subscriber->getStatus();
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

  private function createRunningTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create(
      'bounce',
      null,
      Carbon::now()
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
