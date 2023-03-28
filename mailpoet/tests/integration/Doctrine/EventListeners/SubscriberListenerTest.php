<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use Codeception\Stub\Expected;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Doctrine\EventListeners\SubscriberListener;
use MailPoet\Doctrine\EventListeners\TimestampListener;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/EventListenersBaseTest.php';
require_once __DIR__ . '/TimestampEntity.php';

class SubscriberListenerTest extends EventListenersBaseTest {
  /** @var WPFunctions */
  private $wp;

  /** @var SubscriberListener */
  private $subscriberListener;

  public function _before() {
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItNotifiesAboutNewSubscriber(): void {
    $changesNotifier = $this->make(SubscriberChangesNotifier::class, [
      'wp' => $this->wp,
      'subscriberCreated' => Expected::once(),
    ]);
    $this->subscriberListener = new SubscriberListener($changesNotifier);
    $this->replaceEntityListener($this->subscriberListener);

    (new SubscriberFactory())->create();
  }

  public function testItNotifiesAboutUpdatedSubscriber(): void {
    $subscriber = (new SubscriberFactory())->create();
    $changesNotifier = $this->make(SubscriberChangesNotifier::class, [
      'wp' => $this->wp,
      'subscriberUpdated' => Expected::once(),
    ]);
    $this->subscriberListener = new SubscriberListener($changesNotifier);
    $this->replaceEntityListener($this->subscriberListener);

    $subscriber->setFirstName('John');
    $subscriber->setLastName('Doe');
    $this->entityManager->flush();
  }

  public function testItNotifiesAboutDeletedSubscriber(): void {
    $subscriber = (new SubscriberFactory())->create();
    $changesNotifier = $this->make(SubscriberChangesNotifier::class, [
      'wp' => $this->wp,
      'subscriberDeleted' => Expected::once(),
    ]);
    $this->subscriberListener = new SubscriberListener($changesNotifier);
    $this->replaceEntityListener($this->subscriberListener);

    $this->entityManager->remove($subscriber);
    $this->entityManager->flush();
  }

  public function _after() {
    parent::_after();
    $originalListener = $this->diContainer->get(TimestampListener::class);
    $this->replaceEntityListener($originalListener);
  }
}
