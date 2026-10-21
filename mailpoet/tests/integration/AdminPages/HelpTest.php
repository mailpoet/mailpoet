<?php declare(strict_types = 1);

namespace integration\AdminPages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\AdminPages\Pages\Help;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Logging\LogsPageData;
use MailPoet\Mailer\SendingEnvironment;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\SystemReport\SystemReportCollector;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\WP\Functions as WPFunctions;
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

    $this->helpPage = $this->makeHelpPage($this->diContainer->get(SendingEnvironment::class));
  }

  public function testItReportsNoPauseReasonOnProduction() {
    $environment = new SendingEnvironment(
      $this->diContainer->get(SettingsController::class),
      $this->make(WPFunctions::class, ['wpGetEnvironmentType' => 'production'])
    );
    $data = $this->makeHelpPage($environment)->getQueueStatus();
    verify($data['sendingPausedReason'])->null();
  }

  public function testItReportsWhyTheQueueIsPausedOnANonProductionSite() {
    $environment = new SendingEnvironment(
      $this->diContainer->get(SettingsController::class),
      $this->make(WPFunctions::class, ['wpGetEnvironmentType' => 'staging', 'applyFilters' => fn($hook, $value) => $value])
    );
    $data = $this->makeHelpPage($environment)->getQueueStatus();
    verify($data['sendingPausedReason'])->same('Sending is paused because this is a staging site.');
  }

  public function testItPassesTheLogsDataToTheTemplate() {
    $template = '';
    $data = [];
    $pageRenderer = $this->make(PageRenderer::class, [
      'displayPage' => function ($renderedTemplate, $renderedData = []) use (&$template, &$data) {
        $template = $renderedTemplate;
        $data = $renderedData;
      },
    ]);
    $this->makeHelpPage($this->diContainer->get(SendingEnvironment::class), $pageRenderer)->render();

    verify($template)->same('help.html');
    $logsData = $this->diContainer->get(LogsPageData::class)->get();
    foreach (['logs_default_from', 'api', 'download'] as $key) {
      verify($data)->arrayHasKey($key);
      verify($data[$key])->same($logsData[$key]);
    }
  }

  private function makeHelpPage(SendingEnvironment $sendingEnvironment, ?PageRenderer $pageRenderer = null): Help {
    return new Help(
      $pageRenderer ?? $this->diContainer->get(PageRenderer::class),
      $this->diContainer->get(CronHelper::class),
      $this->diContainer->get(SystemReportCollector::class),
      $this->diContainer->get(Bridge::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->sendingQueuesRepository,
      $this->diContainer->get(Url::class),
      $sendingEnvironment,
      $this->diContainer->get(AssetsController::class),
      $this->diContainer->get(LogsPageData::class)
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
