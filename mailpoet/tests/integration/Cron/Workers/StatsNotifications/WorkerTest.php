<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronHelper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\StatisticsClicks as StatisticsClicksFactory;
use MailPoet\Test\DataFactories\StatisticsOpens as StatisticsOpensFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Carbon\Carbon;
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

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SendingQueueEntity */
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

  /** @var NewsletterFactory */
  private $newsletterFactory;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(StatsNotificationsRepository::class);
    $this->newsletterLinkRepository = $this->diContainer->get(NewsletterLinkRepository::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $this->repository->truncate();
    $this->mailer = $this->createMock(Mailer::class);
    $this->renderer = $this->createMock(Renderer::class);
    $this->settings = SettingsController::getInstance();
    $this->cronHelper = $this->diContainer->get(CronHelper::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
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
    $this->newsletterFactory = new NewsletterFactory();
    $this->newsletter = $this->newsletterFactory
      ->withSubject('Rendered Email Subject')
      ->withSendingQueue(['count_processed' => 5])
      ->create();

    $statsNotificationsTask = $this->scheduledTaskFactory
      ->create(Worker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, new Carbon('2017-01-02 12:13:14'));

    $statsNotificationEntity = new StatsNotificationEntity($this->newsletter, $statsNotificationsTask);
    $this->entityManager->persist($statsNotificationEntity);

    $this->queue = $this->newsletter->getLatestQueue();

    $newsletterEntity = $this->newslettersRepository->findOneById($this->newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletterEntity);
    $this->newsletterLinkFactory = new NewsletterLinkFactory($newsletterEntity);

    $link = $this->newsletterLinkFactory
      ->withUrl('Link url')
      ->withHash('xyz')
      ->create();

    $subscriber1 = (new SubscriberFactory())->create();
    (new StatisticsClicksFactory($link, $subscriber1))->withCount(5)->create();

    $link2 = $this->newsletterLinkFactory
      ->withUrl('Link url2')
      ->withHash('xyzd')
      ->create();

    $subscriber2 = (new SubscriberFactory())->create();
    (new StatisticsClicksFactory($link2, $subscriber2))->withCount(5)->create();
    $subscriber3 = (new SubscriberFactory())->create();
    (new StatisticsClicksFactory($link2, $subscriber3))->withCount(5)->create();

    $subscriber4 = (new SubscriberFactory())->create();
    (new StatisticsOpensFactory($this->newsletter, $subscriber4))->create();
    $subscriber5 = (new SubscriberFactory())->create();
    (new StatisticsOpensFactory($this->newsletter, $subscriber5))->create();

    $this->createStatisticsUnsubscribe([
      'subscriber' => (new SubscriberFactory())->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create(),
      'newsletter' => $this->newslettersRepository->findOneById($this->newsletter->getId()),
      'queue' => $this->sendingQueuesRepository->findOneById($this->queue->getId()),
      'created_at' => '2017-01-02 21:23:45',
    ]);

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

    $subscriber1 = (new SubscriberFactory())->create();
    (new StatisticsClicksFactory($link, $subscriber1))->withCount(1505)->create();
    $subscriber2 = (new SubscriberFactory())->create();
    (new StatisticsClicksFactory($link, $subscriber2))->withCount(2)->create();
    $subscriber3 = (new SubscriberFactory())->create();
    (new StatisticsClicksFactory($link, $subscriber3))->withCount(2)->create();

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
    $this->newsletterFactory
      ->withSubject('Email Subject2')
      ->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSendingQueue()
      ->create();

    $statsNotificationsTask = $this->scheduledTaskFactory
      ->create(Worker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, new Carbon('2016-01-02 12:13:14'));

    $statsNotificationEntity = new StatsNotificationEntity($this->newsletter, $statsNotificationsTask);
    $this->entityManager->persist($statsNotificationEntity);
    $this->entityManager->flush();

    $this->mailer->expects($this->exactly(2))
      ->method('send');

    $this->statsNotifications->process();
  }

  private function createStatisticsUnsubscribe($data): StatisticsUnsubscribeEntity {
    $this->assertInstanceOf(NewsletterEntity::class, $data['newsletter']);
    $this->assertInstanceOf(SendingQueueEntity::class, $data['queue']);
    $this->assertInstanceOf(SubscriberEntity::class, $data['subscriber']);
    $entity = new StatisticsUnsubscribeEntity($data['newsletter'], $data['queue'], $data['subscriber']);
    $entity->setMethod('unknown');
    $this->entityManager->persist($entity);
    if (isset($data['created_at'])) $entity->setCreatedAt(new Carbon($data['created_at']));
    $this->entityManager->flush();
    return $entity;
  }
}
