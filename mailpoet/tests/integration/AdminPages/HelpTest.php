<?php declare(strict_types = 1);

namespace integration\AdminPages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\AdminPages\Pages\Help;
use MailPoet\Cron\CronHelper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Helpscout\Beacon;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Services\Bridge;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
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
    $this->cleanup();
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);

    $this->helpPage = new Help(
      $this->diContainer->get(PageRenderer::class),
      $this->diContainer->get(CronHelper::class),
      $this->diContainer->get(Beacon::class),
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->sendingQueuesRepository,
      $this->diContainer->get(Url::class)
    );
  }

  public function testItFetchesNewsletterDataForSendingTasks() {
    $task = $this->scheduledTaskFactory->create(
      Sending::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::now()->addDay()
    );
    $newsletter = (new Newsletter())
      ->withSubject('Rendered Subject')
      ->create();
    $queue = $this->createNewSendingQueue($task, $newsletter);
    $data = $this->helpPage->buildTaskData($task);
    expect($data['newsletter']['newsletter_id'])->equals($newsletter->getId());
    expect($data['newsletter']['queue_id'])->equals($queue->getId());
    expect($data['newsletter']['subject'])->equals('Rendered Subject');
    expect($data['newsletter']['preview_url'])->notEmpty();
  }

  public function testItDoesNotFailForSendingTaskWithMissingNewsletterInconsistentData() {
    $task = $this->scheduledTaskFactory->create(
      Sending::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      Carbon::now()->addDay()
    );
    $data = $this->helpPage->buildTaskData($task);
    expect($data['newsletter']['newsletter_id'])->equals(null);
    expect($data['newsletter']['queue_id'])->equals(null);
    expect($data['newsletter']['subject'])->equals(null);
    expect($data['newsletter']['preview_url'])->equals(null);
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

  private function cleanup() {
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }

  protected function _after() {
    parent::_after();
    $this->cleanup();
  }
}
