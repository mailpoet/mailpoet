<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Codeception\Stub;
use MailPoet\Config\Renderer;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoetVendor\Idiorm\ORM;
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

  public function _before() {
    parent::_before();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ScheduledTask::createOrUpdate([
      'type' => AutomatedEmails::TASK_TYPE,
      'status' => null,
      'scheduled_at' => '2017-01-02 12:13:14',
      'processed_at' => null,
    ]);
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = SettingsController::getInstance();
    $this->statsNotifications = new AutomatedEmails(
      $this->mailer,
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
    Newsletter::createOrUpdate([
      'id' => 8763,
      'subject' => 'Subject',
      'type' => 'welcome',
      'status' => 'active',
    ]);
    $this->createQueue(8763, 10);
    $this->createClicks(8763, 5);
    $this->createOpens(8763, 2);
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
    Newsletter::createOrUpdate([
      'id' => 8763,
      'subject' => 'Subject',
      'type' => 'welcome',
      'status' => 'active',
    ]);
    $this->createQueue(8763, 10);
    $this->createClicks(8763, 5);
    $this->createOpens(8763, 2);


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
    Newsletter::createOrUpdate([
      'id' => 8764,
      'subject' => 'Subject',
      'type' => 'welcome',
      'status' => 'active',
    ]);
    $this->createClicks(8764, 5);
    $this->createOpens(8764, 2);
    $this->createQueue(8764, 10);
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
    Newsletter::createOrUpdate([
      'id' => 8765,
      'subject' => 'Subject',
      'type' => 'welcome',
      'status' => 'active',
    ]);
    $this->createClicks(8765, 5);
    $this->createOpens(8765, 2);
    $this->createQueue(8765, 10);

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
