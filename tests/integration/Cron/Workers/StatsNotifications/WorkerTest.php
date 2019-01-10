<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Models\StatsNotification;
use PHPUnit\Framework\MockObject\MockObject;

class WorkerTest extends \MailPoetTest {

  /** @var Scheduler */
  private $stats_notifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  function _before() {
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->stats_notifications = new Worker($this->mailer, $this->renderer);
    Setting::setValue(Worker::SETTINGS_KEY, [
      'enabled' => true,
      'address' => 'email@example.com'
    ]);
    $newsletter = Newsletter::createOrUpdate([
      'subject' => 'Email Subject1',
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $sending_task = ScheduledTask::createOrUpdate([
      'type' => 'sending',
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    $stats_notifications_task = ScheduledTask::createOrUpdate([
      'type' => Worker::TASK_TYPE,
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => '2017-01-02 12:13:14',
      'processed_at' => null,
    ]);
    StatsNotification::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $stats_notifications_task->id(),
    ]);
    $queue = SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Email Subject',
      'task_id' => $sending_task->id(),
      'newsletter_id' => $newsletter->id(),
      'count_processed' => 5,
    ]);
    $link = NewsletterLink::createOrUpdate([
      'url' => 'Link url',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'hash' => 'xyz',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'subscriber_id' => '5',
      'link_id' => $link->id(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    $link2 = NewsletterLink::createOrUpdate([
      'url' => 'Link url2',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'hash' => 'xyzd',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'subscriber_id' => '6',
      'link_id' => $link2->id(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'subscriber_id' => '7',
      'link_id' => $link2->id(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsOpens::createOrUpdate([
      'subscriber_id' => '10',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'created_at' => '2017-01-02 12:23:45',
    ]);
    StatisticsOpens::createOrUpdate([
      'subscriber_id' => '11',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'created_at' => '2017-01-02 21:23:45',
    ]);
    StatisticsUnsubscribes::createOrUpdate([
      'subscriber_id' => '12',
      'newsletter_id' => $newsletter->id(),
      'queue_id' => $queue->id(),
      'created_at' => '2017-01-02 21:23:45',
    ]);
  }

  function testRendersTemplate() {
    $this->renderer->expects($this->once())
       ->method('render')
       ->with(
         $this->stringContains('statsNotification.html'),
         $this->callback(function($context){
           return is_array($context);
         }));

    $this->stats_notifications->process();
  }

  function testAddsSubjectToContext() {
    $this->renderer->expects($this->once())
     ->method('render')
     ->with(
       $this->stringContains('statsNotification.html'),
       $this->callback(function($context){
         return $context['subject'] === 'Email Subject1';
       }));

    $this->stats_notifications->process();
  }

  function testAddsPreHeaderToContext() {
    $this->renderer->expects($this->once())
      ->method('render')
      ->with(
       $this->stringContains('statsNotification.html'),
       $this->callback(function($context){
         return $context['preheader'] === '60.00% opens, 40.00% clicks, 20.00% unsubscribes in a nutshell.';
       }));

    $this->stats_notifications->process();
  }

  function testAddsWPUrlsToContext() {
    $this->renderer->expects($this->once())
      ->method('render')
      ->with(
        $this->stringContains('statsNotification.html'),
        $this->callback(function($context){
          return strpos($context['linkSettings'], 'mailpoet-settings')
            && strpos($context['linkStats'], 'mailpoet-newsletters#/stats');
        }));

    $this->stats_notifications->process();
  }

  function testAddsLinksToContext() {
    $this->renderer->expects($this->once())
      ->method('render')
      ->with(
        $this->stringContains('statsNotification.html'),
        $this->callback(function($context){
          return ($context['topLink'] === 'Link url2')
            && ($context['topLinkClicks'] === '2');
        }));

    $this->stats_notifications->process();
  }

  function testSends() {
    $this->mailer->expects($this->once())
      ->method('send');

    $this->stats_notifications->process();
  }

}
