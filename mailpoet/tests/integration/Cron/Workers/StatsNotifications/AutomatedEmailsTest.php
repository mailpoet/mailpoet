<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Codeception\Stub;
use MailPoet\Config\Renderer;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use PHPUnit\Framework\MockObject\MockObject;

class AutomatedEmailsTest extends \MailPoetTest {

  /** @var AutomatedEmails */
  private $statsNotifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var CronWorkerRunner */
  private $cronWorkerRunner;

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before() {
    parent::_before();
    ScheduledTask::createOrUpdate([
      'type' => AutomatedEmails::TASK_TYPE,
      'status' => null,
      'scheduled_at' => '2017-01-02 12:13:14',
      'processed_at' => null,
    ]);
    $this->mailer = $this->createMock(Mailer::class);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')
      ->willReturn($this->mailer);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = SettingsController::getInstance();
    $this->statsNotifications = new AutomatedEmails(
      $mailerFactory,
      $this->renderer,
      $this->settings,
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      new MetaInfo,
      $this->diContainer->get(TrackingConfig::class)
    );
    $this->cronWorkerRunner = Stub::copy($this->diContainer->get(CronWorkerRunner::class), [
      'timer' => microtime(true), // reset timer to avoid timeout during full test suite run
    ]);

    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => 'email@example.com',
    ]);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);

    $newsletterFactory = new NewsletterFactory();
    $this->newsletter = $newsletterFactory
      ->withSubject('Subject')
      ->withWelcomeTypeForSegment(1)
      ->withActiveStatus()
      ->create();
  }

  public function _after() {
    parent::_after();

    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
  }

  public function testItDoesntWorkIfDisabled() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => false,
      'address' => 'email@example.com',
    ]);
    expect($this->statsNotifications->checkProcessingRequirements())->equals(false);
  }

  public function testItDoesntWorkIfNoEmail() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => '',
    ]);
    expect($this->statsNotifications->checkProcessingRequirements())->equals(false);
  }

  public function testItDoesntWorkIfTrackingIsDisabled() {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    expect($this->statsNotifications->checkProcessingRequirements())->equals(false);
  }

  public function testItDoesWorkIfEnabled() {
    expect($this->statsNotifications->checkProcessingRequirements())->equals(true);
  }

  public function testItDoesntRenderIfNoNewslettersFound() {
    $this->renderer->expects($this->never())
      ->method('render');
    $this->mailer->expects($this->never())
      ->method('send');

    $result = $this->cronWorkerRunner->run($this->statsNotifications);

    expect($result)->equals(true);
  }

  public function testItRenders() {
    $this->createQueue($this->newsletter->getId(), 10);
    $this->createClicks($this->newsletter->getId(), 5);
    $this->createOpens($this->newsletter->getId(), 2);
    $this->renderer->expects($this->exactly(2))
      ->method('render');
    $this->renderer->expects($this->at(0))
      ->method('render')
      ->with($this->equalTo('emails/statsNotificationAutomatedEmails.html'));

    $this->renderer->expects($this->at(1))
      ->method('render')
      ->with($this->equalTo('emails/statsNotificationAutomatedEmails.txt'));

    $this->mailer->expects($this->once())
      ->method('send');

    $result = $this->cronWorkerRunner->run($this->statsNotifications);

    expect($result)->equals(true);
  }

  public function testItSends() {
    $this->createQueue($this->newsletter->getId(), 10);
    $this->createClicks($this->newsletter->getId(), 5);
    $this->createOpens($this->newsletter->getId(), 2);

    $this->renderer->expects($this->exactly(2))
      ->method('render');

    $this->mailer->expects($this->once())
      ->method('send')
      ->with(
        $this->callback(function($renderedNewsletter){
          return ($renderedNewsletter['subject'] === 'Your monthly stats are in!')
            && isset($renderedNewsletter['body']);
        }),
        $this->equalTo('email@example.com')
      );

    $result = $this->cronWorkerRunner->run($this->statsNotifications);

    expect($result)->equals(true);
  }

  public function testItPreparesContext() {
    $this->createClicks($this->newsletter->getId(), 5);
    $this->createOpens($this->newsletter->getId(), 2);
    $this->createQueue($this->newsletter->getId(), 10);
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context): bool {
          return (bool)strpos($context['linkSettings'], 'mailpoet-settings');
        }));

    $this->cronWorkerRunner->run($this->statsNotifications);
  }

  public function testItAddsNewsletterStatsToContext() {
    $this->createClicks($this->newsletter->getId(), 5);
    $this->createOpens($this->newsletter->getId(), 2);
    $this->createQueue($this->newsletter->getId(), 10);

    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return strpos($context['newsletters'][0]['linkStats'], 'page=mailpoet-newsletters#/stats')
            && $context['newsletters'][0]['clicked'] === 50
            && $context['newsletters'][0]['opened'] === 20
            && $context['newsletters'][0]['subject'] === 'Subject';
        }));

    $this->cronWorkerRunner->run($this->statsNotifications);
  }

  private function createClicks($newsletterId, $count) {
    for ($i = 0; $i < $count; $i++) {
      StatisticsClicks::createOrUpdate([
        'newsletter_id' => $newsletterId,
        'subscriber_id' => $i + 1,
        'queue_id' => 5,
        'link_id' => 4,
        'count' => 1,
      ]);
    }
  }

  private function createOpens($newsletterId, $count) {
    for ($i = 0; $i < $count; $i++) {
      StatisticsOpens::createOrUpdate([
        'newsletter_id' => $newsletterId,
        'subscriber_id' => $i + 1,
        'queue_id' => 5,
      ]);
    }
  }

  private function createQueue($newsletterId, $countProcessed) {
    $sendingTask = ScheduledTask::createOrUpdate([
      'type' => 'sending',
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Email Subject',
      'task_id' => $sendingTask->id,
      'newsletter_id' => $newsletterId,
      'count_processed' => $countProcessed,
    ]);
  }
}
