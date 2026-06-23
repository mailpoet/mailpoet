<?php declare(strict_types = 1);

namespace integration\AdminPages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\AdminPages\Pages\Help;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Services\Bridge;
use MailPoet\SystemReport\SystemReportCollector;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskQueuedSubscriber as ScheduledTaskQueuedSubscriberFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as ScheduledTaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class HelpTest extends \MailPoetTest {

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /*** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /**
   * @var Help
   */
  private $helpPage;

  public function _before() {
    parent::_before();
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);

    $this->helpPage = new Help(
      $this->diContainer->get(PageRenderer::class),
      $this->diContainer->get(CronHelper::class),
      $this->diContainer->get(SystemReportCollector::class),
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->sendingQueuesRepository,
      $this->diContainer->get(Url::class)
    );
  }

  public function testItFetchesNewsletterDataForSendingTasks() {
    $task = $this->scheduledTaskFactory->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::now()->addDay()
    );
    $newsletter = (new Newsletter())
      ->withSubject('Rendered Subject')
      ->create();
    $queue = $this->createNewSendingQueue($task, $newsletter);
    $data = $this->helpPage->buildTaskData($task);
    verify($data['newsletter']['newsletterId'])->equals($newsletter->getId());
    verify($data['newsletter']['queueId'])->equals($queue->getId());
    verify($data['newsletter']['subject'])->equals('Rendered Subject');
    verify($data['newsletter']['previewUrl'])->notEmpty();
  }

  public function testItDoesNotFailForSendingTaskWithMissingNewsletterInconsistentData() {
    $task = $this->scheduledTaskFactory->create(
      SendingQueue::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::now()->addDay()
    );
    $data = $this->helpPage->buildTaskData($task);
    verify($data['newsletter']['newsletterId'])->equals(null);
    verify($data['newsletter']['queueId'])->equals(null);
    verify($data['newsletter']['subject'])->equals(null);
    verify($data['newsletter']['previewUrl'])->equals(null);
  }

  public function testItExposesRecipientEmailForSingleQueuedRecipient() {
    $task = $this->createSendingTask();
    $subscriber = (new SubscriberFactory())->withEmail('queued@example.com')->create();
    (new ScheduledTaskQueuedSubscriberFactory())->create($task, $subscriber);

    $data = $this->helpPage->buildTaskData($this->reload($task));
    verify($data['subscriberEmail'])->equals('queued@example.com');
  }

  public function testItExposesRecipientEmailForSingleLoggedRecipient() {
    $task = $this->createSendingTask();
    $subscriber = (new SubscriberFactory())->withEmail('logged@example.com')->create();
    (new ScheduledTaskSubscriberFactory())->createProcessed($task, $subscriber);

    $data = $this->helpPage->buildTaskData($this->reload($task));
    verify($data['subscriberEmail'])->equals('logged@example.com');
  }

  public function testItDoesNotExposeRecipientEmailWhenQueueAndLogEachHoldOne() {
    // One recipient already sent (log) and one still pending (queue) => 2 recipients total,
    // so this is not a 1:1 email and no single subscriber email should be exposed.
    $task = $this->createSendingTask();
    $queued = (new SubscriberFactory())->withEmail('pending@example.com')->create();
    $logged = (new SubscriberFactory())->withEmail('sent@example.com')->create();
    (new ScheduledTaskQueuedSubscriberFactory())->create($task, $queued);
    (new ScheduledTaskSubscriberFactory())->createProcessed($task, $logged);

    $data = $this->helpPage->buildTaskData($this->reload($task));
    verify($data['subscriberEmail'])->equals(null);
  }

  private function createSendingTask(): ScheduledTaskEntity {
    // An in-flight sending task has a null status.
    return $this->scheduledTaskFactory->create(
      SendingQueue::TASK_TYPE,
      null,
      Carbon::now()
    );
  }

  // Reload the task as the Help page does (fresh from the DB) so its lazy
  // subscriber collections hydrate from the seeded rows instead of the
  // empty in-memory collections of the just-created entity.
  private function reload(ScheduledTaskEntity $task): ScheduledTaskEntity {
    $id = (int)$task->getId();
    $this->entityManager->clear();
    $reloaded = $this->diContainer->get(ScheduledTasksRepository::class)->findOneById($id);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $reloaded);
    return $reloaded;
  }

  private function createNewSendingQueue(?ScheduledTaskEntity $task, ?NewsletterEntity $newsletter, $renderedSubject = null): SendingQueueEntity {
    $queue = new SendingQueueEntity();
    if ($newsletter instanceof NewsletterEntity) {
      $queue->setNewsletter($newsletter);
    }

    if ($task instanceof ScheduledTaskEntity) {
      $queue->setTask($task);
    }

    $queue->setNewsletterRenderedSubject($renderedSubject);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();
    return $queue;
  }
}
