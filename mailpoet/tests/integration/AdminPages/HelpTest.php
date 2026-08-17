<?php declare(strict_types = 1);

namespace integration\AdminPages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\AdminPages\Pages\Help;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Services\Bridge;
use MailPoet\SystemReport\SystemReportCollector;
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

  public function testItExposesQueueStatusUsingTheKeysTheFrontendReads() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['retry_attempt'] = 2;
    $mailerLog['retry_at'] = 60;
    $mailerLog['error'] = [
      'error_message' => 'SMTP connect failed',
      'operation' => 'send',
    ];

    $data = $this->helpPage->buildQueueStatusData($mailerLog);

    verify($data['retryAttempt'])->equals(2);
    verify($data['retryAt'])->equals(60);
    verify($data['error']['errorMessage'])->equals('SMTP connect failed');
    verify($data['error']['operation'])->equals('send');
  }

  public function testItExposesNullQueueErrorWhenThereIsNoError() {
    $data = $this->helpPage->buildQueueStatusData(MailerLog::createMailerLog());

    verify($data['error'])->equals(null);
    verify($data['retryAttempt'])->equals(null);
    verify($data['retryAt'])->equals(null);
  }

  public function testItKeepsQueueStatusAndStartedAtForTheFrontend() {
    $mailerLog = MailerLog::createMailerLog();
    $mailerLog['started'] = 60;
    $mailerLog['status'] = MailerLog::STATUS_PAUSED;

    $data = $this->helpPage->buildQueueStatusData($mailerLog);

    verify($data['status'])->equals(MailerLog::STATUS_PAUSED);
    verify($data['started'])->equals(60);
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
