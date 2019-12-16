<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Codeception\Stub;
use MailPoet\Config\Renderer;
use MailPoet\Cron\CronWorkerRunner;
use MailPoet\DI\ContainerWrapper;
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
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;

class AutomatedEmailsTest extends \MailPoetTest {

  /** @var AutomatedEmails */
  private $stats_notifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var CronWorkerRunner */
  private $cron_worker_runner;

  function _before() {
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
    $this->stats_notifications = new AutomatedEmails(
      $this->mailer,
      $this->renderer,
      $this->settings,
      ContainerWrapper::getInstance()->get(NewslettersRepository::class),
      ContainerWrapper::getInstance()->get(NewsletterStatisticsRepository::class),
      new MetaInfo
    );
    $this->cron_worker_runner = Stub::copy($this->di_container->get(CronWorkerRunner::class), [
      'timer' => microtime(true), // reset timer to avoid timeout during full test suite run
    ]);

    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => 'email@example.com',
    ]);
    $this->settings->set('tracking.enabled', true);
  }

  function testItDoesntWorkIfDisabled() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => false,
      'address' => 'email@example.com',
    ]);
    expect($this->stats_notifications->checkProcessingRequirements())->equals(false);
  }

  function testItDoesntWorkIfNoEmail() {
    $this->settings->set(Worker::SETTINGS_KEY, [
      'automated' => true,
      'address' => '',
    ]);
    expect($this->stats_notifications->checkProcessingRequirements())->equals(false);
  }

  function testItDoesntWorkIfTrackingIsDisabled() {
    $this->settings->set('tracking.enabled', false);
    expect($this->stats_notifications->checkProcessingRequirements())->equals(false);
  }

  function testItDoesWorkIfEnabled() {
    expect($this->stats_notifications->checkProcessingRequirements())->equals(true);
  }

  function testItDoesntRenderIfNoNewslettersFound() {
    $this->renderer->expects($this->never())
      ->method('render');
    $this->mailer->expects($this->never())
      ->method('send');

    $result = $this->cron_worker_runner->run($this->stats_notifications);

    expect($result)->equals(true);
  }

  function testItRenders() {
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

    $result = $this->cron_worker_runner->run($this->stats_notifications);

    expect($result)->equals(true);
  }

  function testItSends() {
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
        $this->callback(function($rendered_newsletter){
          return ($rendered_newsletter['subject'] === 'Your monthly stats are in!')
            && isset($rendered_newsletter['body']);
        }),
        $this->equalTo('email@example.com')
      );

    $result = $this->cron_worker_runner->run($this->stats_notifications);

    expect($result)->equals(true);
  }

  function testItPreparesContext() {
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
        $this->callback(function($context){
          return strpos($context['linkSettings'], 'mailpoet-settings');
        }));

    $this->cron_worker_runner->run($this->stats_notifications);
  }

  function testItAddsNewsletterStatsToContext() {
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

    $this->cron_worker_runner->run($this->stats_notifications);
  }

  private function createClicks($newsletter_id, $count) {
    for ($i = 0; $i < $count; $i++) {
      StatisticsClicks::createOrUpdate([
        'newsletter_id' => $newsletter_id,
        'subscriber_id' => $i + 1,
        'queue_id' => 5,
        'link_id' => 4,
        'count' => 1,
      ]);
    }
  }

  private function createOpens($newsletter_id, $count) {
    for ($i = 0; $i < $count; $i++) {
      StatisticsOpens::createOrUpdate([
        'newsletter_id' => $newsletter_id,
        'subscriber_id' => $i + 1,
        'queue_id' => 5,
      ]);
    }
  }

  private function createQueue($newsletter_id, $count_processed) {
    $sending_task = ScheduledTask::createOrUpdate([
      'type' => 'sending',
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Email Subject',
      'task_id' => $sending_task->id,
      'newsletter_id' => $newsletter_id,
      'count_processed' => $count_processed,
    ]);
  }

}
