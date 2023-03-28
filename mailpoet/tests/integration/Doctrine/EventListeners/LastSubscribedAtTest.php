<?php declare(strict_types = 1);

namespace MailPoet\Test\Doctrine\EventListeners;

use MailPoet\Doctrine\EventListeners\LastSubscribedAtListener;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/EventListenersBaseTest.php';

class LastSubscribedAtTest extends EventListenersBaseTest {
  /** @var Carbon */
  private $now;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    $timestamp = time();
    $this->now = Carbon::createFromTimestamp($timestamp);
    $this->wp = $this->make(WPFunctions::class, [
      'currentTime' => $timestamp,
    ]);

    $newTimestampListener = new LastSubscribedAtListener($this->wp);
    $originalListener = $this->diContainer->get(LastSubscribedAtListener::class);
    $this->replaceListeners($originalListener, $newTimestampListener);
  }

  public function testItSetsLastSubscribedAtOnCreate() {
    $entity = new SubscriberEntity();
    $entity->setEmail('test@test.com');
    $entity->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);

    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $this->assertEquals($this->now, $entity->getLastSubscribedAt());
  }

  public function testItDoesntSetLastSubscribedAtOnCreateWhenStatusIsNotSubscribed() {
    $entity = new SubscriberEntity();
    $entity->setEmail('test@test.com');
    $entity->setStatus(SubscriberEntity::STATUS_INACTIVE);

    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $this->assertNull($entity->getLastSubscribedAt());
  }

  public function testItSetsLastSubscribedAtOnUpdate() {
    $pastDate = new Carbon('2000-01-01 12:00:00');
    $entity = new SubscriberEntity();
    $entity->setEmail('test@test.com');
    $entity->setStatus(SubscriberEntity::STATUS_INACTIVE);
    $entity->setLastSubscribedAt($pastDate);

    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $this->assertEquals($pastDate, $entity->getLastSubscribedAt());

    $entity->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->flush();

    $this->assertEquals($this->now, $entity->getLastSubscribedAt());
  }

  public function testItDoesntChangeLastSubscribedAtOnUpdateIfStatusIsNotSubscribed() {
    $pastDate = new Carbon('2000-01-01 12:00:00');
    $entity = new SubscriberEntity();
    $entity->setEmail('test@test.com');
    $entity->setStatus(SubscriberEntity::STATUS_INACTIVE);
    $entity->setLastSubscribedAt($pastDate);

    $this->entityManager->persist($entity);
    $this->entityManager->flush();

    $this->assertEquals($pastDate, $entity->getLastSubscribedAt());

    $entity->setEmail('test2@test.com');
    $this->entityManager->flush();

    $this->assertEquals($pastDate, $entity->getLastSubscribedAt());
  }

  public function testItUsesDifferentTimeObjectsWhenCreatingDifferentEntities() {
    $entity1 = new SubscriberEntity();
    $entity1->setEmail('test@test.com');
    $entity1->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);

    $this->entityManager->persist($entity1);
    $this->entityManager->flush();

    $lastSubscribedAt = $entity1->getLastSubscribedAt();
    $this->assertInstanceOf(Carbon::class, $lastSubscribedAt);
    $lastSubscribedAt->subMonth();

    $entity2 = new SubscriberEntity();
    $entity2->setEmail('test2@test.com');
    $entity2->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);

    $this->entityManager->persist($entity2);
    $this->entityManager->flush();

    $this->assertEquals($this->now, $entity2->getLastSubscribedAt());
  }
}
