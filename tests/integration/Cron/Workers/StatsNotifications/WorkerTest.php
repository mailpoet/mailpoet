<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Cron\CronHelper;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\StatisticsUnsubscribes;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use PHPUnit\Framework\MockObject\MockObject;

class WorkerTest extends \MailPoetTest {

  /** @var Worker */
  private $stats_notifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cron_helper;

  /** @var Newsletter */
  private $newsletter;

  /** @var SendingQueue */
  private $queue;

  /** @var StatsNotificationsRepository */
  private $repository;

  /** @var NewsletterLinkRepository */
  private $newsletter_link_repository;

  function _before() {
    parent::_before();
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    $this->repository = ContainerWrapper::getInstance()->get(StatsNotificationsRepository::class);
    $this->newsletter_link_repository = ContainerWrapper::getInstance()->get(NewsletterLinkRepository::class);
    $this->repository->truncate();
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = SettingsController::getInstance();
    $this->cron_helper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->stats_notifications = new Worker(
      $this->mailer,
      $this->renderer,
      $this->settings,
      $this->cron_helper,
      new MetaInfo,
      $this->repository,
      $this->newsletter_link_repository,
      ContainerWrapper::getInstance()->get(NewsletterStatisticsRepository::class),
      $this->entity_manager
    );
    $this->settings->set(Worker::SETTINGS_KEY, [
      'enabled' => true,
      'address' => 'email@example.com',
    ]);
    $this->newsletter = Newsletter::createOrUpdate([
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
    $cmd = $this->entity_manager->getClassMetadata(StatsNotificationEntity::class);
    \ORM::raw_execute('INSERT INTO ' . $cmd->getTableName() . '(newsletter_id, task_id) VALUES ('
      . $this->newsletter->id()
      . ','
      . $stats_notifications_task->id()
      . ')'
    );
    $this->queue = SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Rendered Email Subject',
      'task_id' => $sending_task->id(),
      'newsletter_id' => $this->newsletter->id(),
      'count_processed' => 5,
    ]);
    $link = NewsletterLink::createOrUpdate([
      'url' => 'Link url',
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'hash' => 'xyz',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '5',
      'link_id' => $link->id(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    $link2 = NewsletterLink::createOrUpdate([
      'url' => 'Link url2',
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'hash' => 'xyzd',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '6',
      'link_id' => $link2->id(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '7',
      'link_id' => $link2->id(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsOpens::createOrUpdate([
      'subscriber_id' => '10',
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'created_at' => '2017-01-02 12:23:45',
    ]);
    StatisticsOpens::createOrUpdate([
      'subscriber_id' => '11',
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'created_at' => '2017-01-02 21:23:45',
    ]);
    StatisticsUnsubscribes::createOrUpdate([
      'subscriber_id' => '12',
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'created_at' => '2017-01-02 21:23:45',
    ]);
  }

  function testRendersTemplate() {
    $this->renderer->expects($this->exactly(2))
       ->method('render');
    $this->renderer->expects($this->at(0))
      ->method('render')
      ->with($this->equalTo('emails/statsNotification.html'));

    $this->renderer->expects($this->at(1))
      ->method('render')
      ->with($this->equalTo('emails/statsNotification.txt'));

    $this->stats_notifications->process();
  }

  function testAddsSubjectToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
     ->method('render')
     ->with(
       $this->anything(),
       $this->callback(function($context){
         return $context['subject'] === 'Rendered Email Subject';
       }));

    $this->stats_notifications->process();
  }

  function testAddsPreHeaderToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
       $this->anything(),
       $this->callback(function($context){
         return $context['preheader'] === '40.00% opens, 60.00% clicks, 20.00% unsubscribes in a nutshell.';
       }));

    $this->stats_notifications->process();
  }

  function testAddsWPUrlsToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return strpos($context['linkSettings'], 'mailpoet-settings')
            && strpos($context['linkStats'], 'mailpoet-newsletters#/stats');
        }));

    $this->stats_notifications->process();
  }

  function testAddsLinksToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return ($context['topLink'] === 'Link url2')
            && ($context['topLinkClicks'] === 2);
        }));

    $this->stats_notifications->process();
  }

  function testReplacesShortcodeLinks() {
    $link = NewsletterLink::createOrUpdate([
      'url' => '[link:subscription_manage_url]',
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'hash' => 'xyzd',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '6',
      'link_id' => $link->id(),
      'count' => 1505,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '7',
      'link_id' => $link->id(),
      'count' => 2,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '8',
      'link_id' => $link->id(),
      'count' => 2,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    $this->renderer->expects($this->exactly(2)) // html + text template
    ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return ($context['topLink'] === 'Manage subscription link');
        }));

    $this->stats_notifications->process();
  }

  function testSends() {
    $this->mailer->expects($this->once())
      ->method('send');

    $this->stats_notifications->process();
  }

  function testItWorksForNewsletterWithNoStats() {
    $newsletter = Newsletter::createOrUpdate([
      'subject' => 'Email Subject2',
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $sending_task = ScheduledTask::createOrUpdate([
      'type' => 'sending',
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    $stats_notifications_task = ScheduledTask::createOrUpdate([
      'type' => Worker::TASK_TYPE,
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => '2016-01-02 12:13:14',
      'processed_at' => null,
    ]);
    $cmd = $this->entity_manager->getClassMetadata(StatsNotificationEntity::class);
    \ORM::raw_execute('INSERT INTO ' . $cmd->getTableName() . '(newsletter_id, task_id) VALUES ('
      . $this->newsletter->id()
      . ','
      . $stats_notifications_task->id()
      . ')'
    );
    SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Rendered Email Subject2',
      'task_id' => $sending_task->id(),
      'newsletter_id' => $newsletter->id(),
      'count_processed' => 15,
    ]);

    $this->mailer->expects($this->exactly(2))
      ->method('send');

    $this->stats_notifications->process();
  }

}
