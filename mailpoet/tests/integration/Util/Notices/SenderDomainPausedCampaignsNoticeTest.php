<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Util\FreeDomains;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class SenderDomainPausedCampaignsNoticeTest extends \MailPoetTest {
  private SettingsController $settings;

  /** @var ScheduledTasksRepository&\PHPUnit\Framework\MockObject\MockObject */
  private $tasksRepository;

  /** @var AuthorizedSenderDomainController&\PHPUnit\Framework\MockObject\MockObject */
  private $senderDomainController;

  private bool $canManageEmails = true;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $this->tasksRepository = $this->createMock(ScheduledTasksRepository::class);
    $this->senderDomainController = $this->createMock(AuthorizedSenderDomainController::class);
    $this->senderDomainController->method('isBigSender')->willReturn(true);
    $this->canManageEmails = true;
  }

  public function _after() {
    $this->settings->delete('mta.method');
    parent::_after();
  }

  public function testItReturnsNullWhenShouldNotDisplay() {
    $this->tasksRepository->expects($this->never())->method('findPausedForSenderDomain');
    verify($this->createNotice()->init(false))->null();
  }

  public function testItReturnsNullWhenNotUsingMailPoetSendingService() {
    $this->settings->set('mta.method', Mailer::METHOD_PHPMAIL);
    $this->tasksRepository->expects($this->never())->method('findPausedForSenderDomain');
    verify($this->createNotice()->init(true))->null();
  }

  public function testItReturnsNullWithoutCapability() {
    $this->canManageEmails = false;
    $this->tasksRepository->expects($this->never())->method('findPausedForSenderDomain');
    verify($this->createNotice()->init(true))->null();
  }

  public function testItReturnsNullWhenNothingIsPaused() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([]);
    verify($this->createNotice()->init(true))->null();
  }

  public function testItRendersBrandedDomainSingleCampaign() {
    $task = $this->makeTask(7, 'Spring sale', 'hello@example.com');
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([$task]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify($message)->stringContainsString('1 campaign is paused: “<a href="');
    verify($message)->stringContainsString('#/send/7">Spring sale</a>”');
    verify($message)->stringContainsString('Your site has more than 200 subscribers, so campaigns must be sent from an authenticated domain.');
    verify($message)->stringContainsString('Authenticate example.com or change the From address, then resume it.');
    verify($message)->stringContainsString('class="button button-primary mailpoet-js-button-authorize-email-and-sender-domain" data-email="hello@example.com" data-type="domain">Authenticate domain</a>');
    verify($message)->stringNotContainsString('is-dismissible');
    $html = $this->render($notice);
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringNotContainsString('is-dismissible');
    verify($html)->stringNotContainsString('data-notice=');
    verify($html)->stringContainsString('data-email="hello@example.com" data-type="domain"');
    verify($html)->stringContainsString('mailpoet-js-button-authorize-email-and-sender-domain');
  }

  public function testItRendersOneButtonPerDomainGroup() {
    $tasks = [];
    for ($i = 1; $i <= 5; $i++) {
      $tasks[] = $this->makeTask($i, "Campaign $i", "sender$i@example.com");
    }
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn($tasks);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify(substr_count($message, 'mailpoet-js-button-authorize-email-and-sender-domain'))->equals(1);
    verify($message)->stringContainsString('data-email="sender1@example.com"');
    verify($message)->stringContainsString('5 campaigns are paused:');
    verify($message)->stringContainsString('then resume them.');
    verify($message)->stringContainsString('“<a href="');
    verify($message)->stringContainsString('>Campaign 3</a>”');
    verify($message)->stringNotContainsString('Campaign 4');
    verify($message)->stringContainsString('and 2 more</a>');
    verify($message)->stringContainsString(admin_url('admin.php?page=mailpoet-newsletters') . '"');
  }

  public function testItListsEachNewsletterOnceWhenItHasMultipleTasks() {
    $first = $this->makeTask(7, 'Timezone campaign', 'hello@example.com');
    $second = $this->makeTask(7, 'Timezone campaign', 'hello@example.com');
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([$first, $second]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify($message)->stringContainsString('1 campaign is paused:');
    verify(substr_count($message, 'Timezone campaign'))->equals(1);
  }

  public function testItRendersFreeMailGroupWithoutButton() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(3, 'Newsletter', 'me@gmail.com'),
    ]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify($message)->stringContainsString('campaigns can’t be sent from a shared address like gmail.com. Change the From address to one on your own domain, then resume it.');
    verify($message)->stringNotContainsString('mailpoet-js-button-authorize-email-and-sender-domain');
  }

  public function testItRendersGenericGroupForEmptySender() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(3, 'Newsletter', ''),
    ]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify($message)->stringContainsString('campaigns must be sent from an authenticated domain. Change the From address, then resume it.');
    verify($message)->stringNotContainsString('mailpoet-js-button-authorize-email-and-sender-domain');
    verify($message)->stringNotContainsString('Authenticate');
  }

  public function testItRendersOneNoticeWithOneParagraphPerGroupInOrder() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(1, 'Generic', ''),
      $this->makeTask(2, 'Free', 'me@gmail.com'),
      $this->makeTask(3, 'Branded', 'me@example.com'),
    ]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify(substr_count($message, '<p>'))->equals(3);
    $branded = strpos($message, 'Branded');
    $free = strpos($message, 'Free');
    $generic = strpos($message, 'Generic');
    $this->assertTrue($branded < $free && $free < $generic);
  }

  public function testItLinksPostNotificationHistoryToParent() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(9, 'Digest', 'me@example.com', NewsletterEntity::TYPE_NOTIFICATION_HISTORY, 4),
    ]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    verify($notice->getMessage())->stringContainsString('#/notification/history/4">Digest</a>');
  }

  public function testItMentionsSubscriberCountOnlyForBigSenders() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(1, 'Campaign', 'me@example.com'),
    ]);
    $notice = $this->createNotice()->init(true);
    $this->assertInstanceOf(Notice::class, $notice);
    verify($notice->getMessage())->stringContainsString('more than 200 subscribers');

    $this->senderDomainController = $this->createMock(AuthorizedSenderDomainController::class);
    $this->senderDomainController->method('isBigSender')->willReturn(false);
    $notice = $this->createNotice()->init(true);
    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify($message)->stringNotContainsString('subscribers');
    verify($message)->stringContainsString('Campaigns must be sent from an authenticated domain.');
  }

  public function testItEscapesCampaignNames() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(1, '<b>Hi</b> & "x"', 'me@example.com'),
    ]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    $message = $notice->getMessage();
    verify($message)->stringContainsString('&lt;b&gt;Hi&lt;/b&gt; &amp; &quot;x&quot;');
    verify($message)->stringNotContainsString('<b>Hi</b>');
  }

  public function testItFallsBackToNoSubjectLabel() {
    $this->tasksRepository->method('findPausedForSenderDomain')->willReturn([
      $this->makeTask(1, '', 'me@example.com'),
    ]);

    $notice = $this->createNotice()->init(true);

    $this->assertInstanceOf(Notice::class, $notice);
    verify($notice->getMessage())->stringContainsString('>(No subject)</a>');
  }

  public function testItStopsShowingCampaignAfterResume() {
    $newsletter = (new NewsletterFactory())->withSubject('End to end')->withSenderAddress('me@example.com')->create();
    $task = (new ScheduledTaskFactory())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_PAUSED);
    $queue = (new SendingQueueFactory())->create($task, $newsletter);
    $queue->setCountTotal(2);
    $task->setPauseReason(ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN);
    $this->entityManager->flush();

    $notice = new SenderDomainPausedCampaignsNotice(
      $this->wpWithCapability(),
      $this->diContainer->get(ScheduledTasksRepository::class),
      new FreeDomains(),
      $this->settings,
      $this->senderDomainController
    );

    $result = $notice->init(true);
    $this->assertInstanceOf(Notice::class, $result);
    verify($result->getMessage())->stringContainsString('End to end');

    $this->diContainer->get(SendingQueuesRepository::class)->resume($queue);

    verify($notice->init(true))->null();
  }

  private function render(Notice $notice): string {
    ob_start();
    $notice->displayWPNotice();
    return (string)ob_get_clean();
  }

  private function wpWithCapability(): WPFunctions {
    $canManage = &$this->canManageEmails;
    $wp = $this->createMock(WPFunctions::class);
    $real = new WPFunctions();
    $wp->method('currentUserCan')->willReturnCallback(function ($capability) use (&$canManage) {
      return $capability === AccessControl::PERMISSION_MANAGE_EMAILS && $canManage;
    });
    foreach (['adminUrl', 'escUrl', 'escAttr', 'escHtml'] as $method) {
      $wp->method($method)->willReturnCallback(fn(...$args) => $real->$method(...$args));
    }
    return $wp;
  }

  private function createNotice(): SenderDomainPausedCampaignsNotice {
    return new SenderDomainPausedCampaignsNotice(
      $this->wpWithCapability(),
      $this->tasksRepository,
      new FreeDomains(),
      $this->settings,
      $this->senderDomainController
    );
  }

  private function makeTask(int $newsletterId, string $subject, string $senderAddress, string $type = NewsletterEntity::TYPE_STANDARD, ?int $parentId = null): ScheduledTaskEntity {
    $newsletter = $this->createMock(NewsletterEntity::class);
    $newsletter->method('getId')->willReturn($newsletterId);
    $newsletter->method('getCampaignNameOrSubject')->willReturn($subject);
    $newsletter->method('getSenderAddress')->willReturn($senderAddress);
    $newsletter->method('getType')->willReturn($type);
    if ($parentId !== null) {
      $parent = $this->createMock(NewsletterEntity::class);
      $parent->method('getId')->willReturn($parentId);
      $newsletter->method('getParent')->willReturn($parent);
    }
    $queue = $this->createMock(\MailPoet\Entities\SendingQueueEntity::class);
    $queue->method('getNewsletter')->willReturn($newsletter);
    $task = $this->createMock(ScheduledTaskEntity::class);
    $task->method('getSendingQueue')->willReturn($queue);
    return $task;
  }
}
