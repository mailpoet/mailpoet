<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics\Track;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class UnsubscribesTest extends \MailPoetTest {
  /** @var Unsubscribes */
  private $unsubscribes;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  public $queue;

  /** @var SubscriberEntity */
  public $subscriber;

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
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->getId();
    $queue->setSubscribers([$this->subscriber->getId()]);
    $queue->updateProcessedSubscribers([$this->subscriber->getId()]);
    $this->queue = $queue->save();
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
      (int)$this->queue->id,
      null,
      StatisticsUnsubscribeEntity::METHOD_ONE_CLICK
    );
    $allStats = $this->statisticsUnsubscribesRepository->findAll();
    expect(count($allStats))->equals(1);
    expect($allStats[0]->getMethod())->equals(StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
  }

  public function testItDoesNotTrackRepeatedUnsubscribeEvents() {
    $subscriberId = $this->subscriber->getId();
    $this->assertIsInt($subscriberId);

    for ($count = 0; $count <= 2; $count++) {
      $this->unsubscribes->track(
        $subscriberId,
        'source',
        (int)$this->queue->id
      );
    }
    expect(count($this->statisticsUnsubscribesRepository->findAll()))->equals(1);
  }
}
