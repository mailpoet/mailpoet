<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Util\Stub;
use DateTime;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Cron\Workers\WooCommercePastOrders;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Statistics\StatisticsClicksRepository;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group woo
 */
class WooCommerceOrdersTest extends \MailPoetTest {
  /** @var MockObject */
  private $woocommerceHelper;

  /** @var MockObject */
  private $woocommercePurchases;

  /** @var WooCommercePastOrders */
  private $worker;

  /** @var ScheduledTasksRepository */
  private $scheduledTaskRepository;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  public function _before() {
    $this->woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $this->woocommercePurchases = $this->createMock(WooCommercePurchases::class);

    $this->worker = new WooCommercePastOrders(
      $this->woocommerceHelper,
      $this->diContainer->get(StatisticsClicksRepository::class),
      $this->woocommercePurchases
    );
    $this->cronWorkerRunner = Stub::copy($this->diContainer->get(CronWorkerRunner::class), [
      'timer' => microtime(true), // reset timer to avoid timeout during full test suite run
    ]);
    $this->scheduledTaskRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItDoesNotRunIfWooCommerceIsDisabled() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(false);
    expect($this->worker->checkProcessingRequirements())->false();

    $this->cronWorkerRunner->run($this->worker);
    $tasks = $this->scheduledTaskRepository->findBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    expect($tasks)->isEmpty();
  }

  public function testItRunsIfWooCommerceIsEnabled() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    expect($this->worker->checkProcessingRequirements())->true();

    $this->cronWorkerRunner->run($this->worker);
    $tasks = $this->scheduledTaskRepository->findBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    expect($tasks)->count(1);
  }

  public function testItRunsOnlyOnce() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturn([]);

    // 1. schedule
    expect($this->worker->checkProcessingRequirements())->true();
    $this->cronWorkerRunner->run($this->worker);
    $task = $this->scheduledTaskRepository->findOneBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);

    // 2. prepare and run
    expect($this->worker->checkProcessingRequirements())->true();
    $this->cronWorkerRunner->run($this->worker);
    $this->entityManager->clear();
    $task = $this->scheduledTaskRepository->findOneBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);

    // 3. complete (do not schedule again)
    expect($this->worker->checkProcessingRequirements())->false();
    $this->cronWorkerRunner->run($this->worker);
    $this->entityManager->clear();
    $task = $this->scheduledTaskRepository->findOneBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);

    $this->entityManager->clear();
    $tasks = $this->scheduledTaskRepository->findBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    expect($tasks)->count(1);
  }

  public function testItTracksOrders() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturn([1, 2, 3]);
    $this->createClick();

    $this->woocommercePurchases->expects($this->exactly(3))->method('trackPurchase');

    $this->cronWorkerRunner->run($this->worker); // schedule
    $this->cronWorkerRunner->run($this->worker); // prepare and run

    $this->entityManager->clear();
    $tasks = $this->scheduledTaskRepository->findBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    expect($tasks)->count(1);
    expect($tasks[0]->getStatus())->equals(null); // null means 'running'
  }

  public function testItContinuesFromLastId() {
    $this->woocommerceHelper->method('isWooCommerceActive')->willReturn(true);
    $this->woocommerceHelper->method('wcGetOrders')->willReturnOnConsecutiveCalls([1, 2, 3], [4, 5], []);
    $this->createClick();

    $this->woocommercePurchases->expects($this->exactly(5))->method('trackPurchase');

    $this->cronWorkerRunner->run($this->worker); // schedule
    $this->cronWorkerRunner->run($this->worker); // prepare and run for 1, 2, 3

    $task = $this->scheduledTaskRepository->findOneBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getMeta())->equals(['last_processed_id' => 3]);

    $this->cronWorkerRunner->run($this->worker); // run for 4, 5

    $task = $this->scheduledTaskRepository->findOneBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    expect($task->getMeta())->equals(['last_processed_id' => 5]);

    $this->cronWorkerRunner->run($this->worker); // complete

    $this->entityManager->clear();
    $tasks = $this->scheduledTaskRepository->findBy(['type' => WooCommercePastOrders::TASK_TYPE]);
    expect($tasks)->count(1);
    expect($tasks[0]->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  private function createClick($createdDaysAgo = 5) {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($newsletter);

    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    $link = new NewsletterLinkEntity($newsletter, $queue, 'http://example1.com', 'abcd');
    $this->entityManager->persist($link);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail("sub{$newsletter->getId()}@mailpoet.com");
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);

    $click = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
    $this->entityManager->persist($click);

    $timestamp = new DateTime("-$createdDaysAgo days");
    $click->setCreatedAt($timestamp);
    $click->setUpdatedAt($timestamp);
    $this->entityManager->flush();
  }
}
