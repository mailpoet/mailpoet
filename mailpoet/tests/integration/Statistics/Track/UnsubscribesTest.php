<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics\Track;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class UnsubscribesTest extends \MailPoetTest {
  /** @var Unsubscribes */
  private $unsubscribes;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  /** @var SubscriberEntity */
  public $subscriber;

  /** @var SendingQueueEntity */
  private $sendingQueue;

  public function _before() {
    parent::_before();

    // create newsletter
    $newsletterFactory = new NewsletterFactory();
    $newsletter = $newsletterFactory->withType('type')->create();

    // create subscriber
    $subscriberFactory = new SubscriberFactory();
    $this->subscriber = $subscriberFactory
      ->withEmail('test@example.com')
      ->withFirstName('First')
      ->withLastName('Last')
      ->create();

    // create queue
    $scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $scheduledTask = (new ScheduledTaskFactory())->create(SendingQueue::TASK_TYPE, null);
    $this->sendingQueue = (new SendingQueueFactory())->create($scheduledTask, $newsletter);
    $scheduledTaskSubscribersRepository->setSubscribers($scheduledTask, [$this->subscriber->getId()]);
    $scheduledTaskSubscribersRepository->updateProcessedSubscribers($scheduledTask, [(int)$this->subscriber->getId()]);

    // instantiate class
    $this->unsubscribes = $this->diContainer->get(Unsubscribes::class);
    $this->statisticsUnsubscribesRepository = $this->diContainer->get(StatisticsUnsubscribesRepository::class);
  }

  public function testItTracksUnsubscribeEvent() {
    $subscriberId = $this->subscriber->getId();
    $this->assertIsInt($subscriberId);
    $this->unsubscribes->track(
      $subscriberId,
      'source',
      (int)$this->sendingQueue->getId(),
      null,
      StatisticsUnsubscribeEntity::METHOD_ONE_CLICK
    );
    $allStats = $this->statisticsUnsubscribesRepository->findAll();
    verify(count($allStats))->equals(1);
    verify($allStats[0]->getMethod())->equals(StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
  }

  public function testItDoesNotTrackRepeatedUnsubscribeEvents() {
    $subscriberId = $this->subscriber->getId();
    $this->assertIsInt($subscriberId);

    for ($count = 0; $count <= 2; $count++) {
      $this->unsubscribes->track(
        $subscriberId,
        'source',
        (int)$this->sendingQueue->getId()
      );
    }
    verify(count($this->statisticsUnsubscribesRepository->findAll()))->equals(1);
  }

  public function testItTracksBulkUnsubscribesWithoutLoadingEntities(): void {
    $active = (new SubscriberFactory())->withEmail('active@example.com')->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $inactive = (new SubscriberFactory())->withEmail('inactive@example.com')->withStatus(SubscriberEntity::STATUS_INACTIVE)->create();
    $unsubscribed = (new SubscriberFactory())->withEmail('done@example.com')->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $missingId = (int)$unsubscribed->getId() + 1000;

    $count = $this->unsubscribes->trackBulk(
      [(int)$active->getId(), (int)$inactive->getId(), (int)$unsubscribed->getId(), $missingId],
      StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR
    );

    verify($count)->equals(2);
    $this->entityManager->clear();
    verify($this->statisticsUnsubscribesRepository->findAll())->arrayCount(2);
    verify($this->statisticsUnsubscribesRepository->findBy(['subscriber' => $unsubscribed]))->arrayCount(0);
    $activeStats = $this->statisticsUnsubscribesRepository->findBy(['subscriber' => $active]);
    verify($activeStats)->arrayCount(1);
    verify($this->statisticsUnsubscribesRepository->findBy(['subscriber' => $inactive]))->arrayCount(1);
    verify($activeStats[0]->getSource())->equals(StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR);
    verify($activeStats[0]->getMethod())->equals(StatisticsUnsubscribeEntity::METHOD_UNKNOWN);
    verify($activeStats[0]->getCreatedAt())->notNull();
    verify($activeStats[0]->getNewsletter())->null();
    verify($activeStats[0]->getQueue())->null();
  }

  public function testBulkTrackingOfEmptySelectionInsertsNothing(): void {
    verify($this->unsubscribes->trackBulk([], StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR))->equals(0);
    verify($this->statisticsUnsubscribesRepository->findAll())->arrayCount(0);
  }
}
