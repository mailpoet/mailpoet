<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronHelper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;

class WorkerTest extends \MailPoetTest {

  /** @var Worker */
  private $statsNotifications;

  /** @var MockObject */
  private $mailer;

  /** @var MockObject */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  /** @var Newsletter */
  private $newsletter;

  /** @var SendingQueue */
  private $queue;

  /** @var StatsNotificationsRepository */
  private $repository;

  /** @var NewsletterLinkFactory */
  private $newsletterLinkFactory;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(StatsNotificationsRepository::class);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->repository->truncate();
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = SettingsController::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')
      ->willReturn($this->mailer);
    $this->statsNotifications = new Worker(
      $mailerFactory,
      $this->renderer,
      $this->settings,
      $this->cronHelper,
      new MetaInfo,
      $this->repository,
      $this->newsletterLinkRepository,
      $this->diContainer->get(NewsletterStatisticsRepository::class),
      $this->entityManager,
      $this->diContainer->get(SubscribersFeature::class),
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(ServicesChecker::class)
    );
    $this->settings->set(Worker::SETTINGS_KEY, [
      'enabled' => true,
      'address' => 'email@example.com',
    ]);
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'Email Subject1',
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $sendingTask = ScheduledTask::createOrUpdate([
      'type' => 'sending',
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    $statsNotificationsTask = ScheduledTask::createOrUpdate([
      'type' => Worker::TASK_TYPE,
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => '2017-01-02 12:13:14',
      'processed_at' => null,
    ]);
    $cmd = $this->entityManager->getClassMetadata(StatsNotificationEntity::class);
    ORM::raw_execute('INSERT INTO ' . $cmd->getTableName() . '(newsletter_id, task_id) VALUES ('
      . $this->newsletter->id()
      . ','
      . $statsNotificationsTask->id()
      . ')'
    );
    $this->queue = SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Rendered Email Subject',
      'task_id' => $sendingTask->id(),
      'newsletter_id' => $this->newsletter->id(),
      'count_processed' => 5,
    ]);

    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterLinkFactory = new NewsletterLinkFactory($newsletterEntity);

    $link = $this->newsletterLinkFactory
      ->withUrl('Link url')
      ->withHash('xyz')
      ->create();

    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '5',
      'link_id' => $link->getId(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);

    $link2 = $this->newsletterLinkFactory
      ->withUrl('Link url2')
      ->withHash('xyzd')
      ->create();
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '6',
      'link_id' => $link2->getId(),
      'count' => 5,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '7',
      'link_id' => $link2->getId(),
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
    $this->createStatisticsUnsubscribe([
      'subscriber' => $this->createSubscriber(),
      'newsletter' => $this->newslettersRepository->findOneById($this->newsletter->id()),
      'queue' => $this->sendingQueuesRepository->findOneById($this->queue->id()),
      'created_at' => '2017-01-02 21:23:45',
    ]);

    // need as for now we are creating the clicks outside of Doctrine using the old StatisticsClicks class
    $this->entityManager->refresh($link);
    $this->entityManager->refresh($link2);
  }

  public function testRendersTemplate() {
    $this->renderer->expects($this->exactly(2))
       ->method('render');
    $this->renderer->expects($this->at(0))
      ->method('render')
      ->with($this->equalTo('emails/statsNotification.html'));

    $this->renderer->expects($this->at(1))
      ->method('render')
      ->with($this->equalTo('emails/statsNotification.txt'));

    $this->statsNotifications->process();
  }

  public function testAddsSubjectToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
     ->method('render')
     ->with(
       $this->anything(),
       $this->callback(function($context){
         return $context['subject'] === 'Rendered Email Subject';
       }));

    $this->statsNotifications->process();
  }

  public function testAddsPreHeaderToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
       $this->anything(),
       $this->callback(function($context){
         return $context['preheader'] === '60.00% clicks, 40.00% opens, 20.00% unsubscribes in a nutshell.';
       }));

    $this->statsNotifications->process();
  }

  public function testAddsWPUrlsToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return strpos($context['linkSettings'], 'mailpoet-settings')
            && strpos($context['linkStats'], 'mailpoet-newsletters&stats');
        }));

    $this->statsNotifications->process();
  }

  public function testAddsLinksToContext() {
    $this->renderer->expects($this->exactly(2)) // html + text template
      ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return ($context['topLink'] === 'Link url2')
            && ($context['topLinkClicks'] === 2);
        }));

    $this->statsNotifications->process();
  }

  public function testReplacesShortcodeLinks() {
    $link = $this->newsletterLinkFactory
      ->withUrl('[link:subscription_manage_url]')
      ->withHash('xyzd')
      ->create();

    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '6',
      'link_id' => $link->getId(),
      'count' => 1505,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '7',
      'link_id' => $link->getId(),
      'count' => 2,
      'created_at' => '2018-01-02 15:16:17',
    ]);
    StatisticsClicks::createOrUpdate([
      'newsletter_id' => $this->newsletter->id(),
      'queue_id' => $this->queue->id(),
      'subscriber_id' => '8',
      'link_id' => $link->getId(),
      'count' => 2,
      'created_at' => '2018-01-02 15:16:17',
    ]);

    // need as for now we are creating the clicks outside of Doctrine using the old StatisticsClicks class
    $this->entityManager->refresh($link);

    $this->renderer->expects($this->exactly(2)) // html + text template
    ->method('render')
      ->with(
        $this->anything(),
        $this->callback(function($context){
          return ($context['topLink'] === 'Manage subscription link');
        }));

    $this->statsNotifications->process();
  }

  public function testSends() {
    $this->mailer->expects($this->once())
      ->method('send');

    $this->statsNotifications->process();
  }

  public function testItWorksForNewsletterWithNoStats() {
    $newsletter = Newsletter::createOrUpdate([
      'subject' => 'Email Subject2',
      'type' => Newsletter::TYPE_STANDARD,
    ]);
    $sendingTask = ScheduledTask::createOrUpdate([
      'type' => 'sending',
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    $statsNotificationsTask = ScheduledTask::createOrUpdate([
      'type' => Worker::TASK_TYPE,
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => '2016-01-02 12:13:14',
      'processed_at' => null,
    ]);
    $cmd = $this->entityManager->getClassMetadata(StatsNotificationEntity::class);
    ORM::raw_execute('INSERT INTO ' . $cmd->getTableName() . '(newsletter_id, task_id) VALUES ('
      . $this->newsletter->id()
      . ','
      . $statsNotificationsTask->id()
      . ')'
    );
    SendingQueue::createOrUpdate([
      'newsletter_rendered_subject' => 'Rendered Email Subject2',
      'task_id' => $sendingTask->id(),
      'newsletter_id' => $newsletter->id(),
      'count_processed' => 15,
    ]);

    $this->mailer->expects($this->exactly(2))
      ->method('send');

    $this->statsNotifications->process();
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail('subscriber' . rand(0, 10000) . '@example.com');
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createStatisticsUnsubscribe($data): StatisticsUnsubscribeEntity {
    assert($data['newsletter'] instanceof NewsletterEntity);
    assert($data['queue'] instanceof SendingQueueEntity);
    assert($data['subscriber'] instanceof SubscriberEntity);
    $entity = new StatisticsUnsubscribeEntity($data['newsletter'], $data['queue'], $data['subscriber']);
    $this->entityManager->persist($entity);
    if (isset($data['created_at'])) $entity->setCreatedAt(new Carbon($data['created_at']));
    $this->entityManager->flush();
    return $entity;
  }

  private function cleanup() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsClicks::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    $this->truncateEntity(StatisticsUnsubscribeEntity::class);
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }
}
