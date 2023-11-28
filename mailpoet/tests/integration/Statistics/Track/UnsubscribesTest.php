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
}
