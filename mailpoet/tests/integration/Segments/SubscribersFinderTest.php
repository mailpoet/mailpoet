<?php

namespace MailPoet\Segments;

use Codeception\Util\Stub;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use PHPUnit\Framework\MockObject\MockObject;

class SubscribersFinderTest extends \MailPoetTest {
  public $sending;
  public $subscriber3;
  public $subscriber2;
  public $subscriber1;
  public $segment3;
  public $segment2;
  public $segment1;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $segmentFactory = new SegmentFactory();
    $this->segment1 = $segmentFactory->withName('Segment 1')->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->segment2 = $segmentFactory->withName('Segment 2')->withType(SegmentEntity::TYPE_DEFAULT)->create();
    $this->segment3 = $segmentFactory->withName('Segment 3')->withType(SegmentEntity::TYPE_DYNAMIC)->create();

    $subscriberFactory = new SubscriberFactory();
    $this->subscriber1 = $subscriberFactory
      ->withEmail('john@mailpoet.com')
      ->create();
    $this->subscriber2 = $subscriberFactory
      ->withEmail('jane@mailpoet.com')
      ->withSegments([$this->segment1])
      ->create();
    $this->subscriber3 = $subscriberFactory
      ->withEmail('jake@mailpoet.com')
      ->withSegments([$this->segment3])
      ->create();

    $this->sending = SendingTask::create();
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersFinder = $this->diContainer->get(SubscribersFinder::class);
  }

  public function testFindSubscribersInSegmentInSegmentDefaultSegment() {
    $deletedSegmentId = 1000; // non-existent segment
    $subscribers = $this->subscribersFinder->findSubscribersInSegments([$this->subscriber2->getId()], [$this->segment1->getId(), $deletedSegmentId]);
    expect($subscribers)->count(1);
    expect($subscribers[$this->subscriber2->getId()])->equals($this->subscriber2->getId());
  }

  public function testFindSubscribersInSegmentUsingFinder() {
    /** @var SegmentSubscribersRepository & MockObject $mock */
    $mock = Stub::makeEmpty(SegmentSubscribersRepository::class, ['findSubscribersIdsInSegment']);
    $mock
      ->expects($this->once())
      ->method('findSubscribersIdsInSegment')
      ->will($this->returnValue([$this->subscriber3->getId()]));

    $finder = new SubscribersFinder($mock, $this->segmentsRepository);
    $subscribers = $finder->findSubscribersInSegments([$this->subscriber3->getId()], [$this->segment3->getId()]);
    expect($subscribers)->count(1);
    expect($subscribers)->contains($this->subscriber3->getId());
  }

  public function testFindSubscribersInSegmentUsingFinderMakesResultUnique() {
    /** @var SegmentSubscribersRepository & MockObject $mock */
    $mock = Stub::makeEmpty(SegmentSubscribersRepository::class, ['findSubscribersIdsInSegment']);
    $mock
      ->expects($this->exactly(2))
      ->method('findSubscribersIdsInSegment')
      ->will($this->returnValue([$this->subscriber3->getId()]));

    $finder = new SubscribersFinder($mock, $this->segmentsRepository);
    $subscribers = $finder->findSubscribersInSegments([$this->subscriber3->getId()], [$this->segment3->getId(), $this->segment3->getId()]);
    expect($subscribers)->count(1);
  }

  public function testItAddsSubscribersToTaskFromStaticSegments() {
    $subscribersCount = $this->subscribersFinder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->segment1->getId(),
        $this->segment2->getId(),
      ]
    );
    expect($subscribersCount)->equals(1);
    expect($this->sending->getSubscribers())->equals([$this->subscriber2->getId()]);
  }

  public function testItDoesNotAddSubscribersToTaskFromNoSegment() {
    $this->segment3->setType('Invalid type');
    $subscribersCount = $this->subscribersFinder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->segment3->getId(),
      ]
    );
    expect($subscribersCount)->equals(0);
  }

  public function testItAddsSubscribersToTaskFromDynamicSegments() {
    /** @var SegmentSubscribersRepository & MockObject $mock */
    $mock = Stub::makeEmpty(SegmentSubscribersRepository::class, ['getSubscriberIdsInSegment']);
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue([$this->subscriber1->getId()]));
    $this->segment2->setType(SegmentEntity::TYPE_DYNAMIC);

    $finder = new SubscribersFinder($mock, $this->segmentsRepository);
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->segment2->getId(),
      ]
    );
    expect($subscribersCount)->equals(1);
    expect($this->sending->getSubscribers())->equals([$this->subscriber1->getId()]);
  }

  public function testItAddsSubscribersToTaskFromStaticAndDynamicSegments() {
    /** @var SegmentSubscribersRepository & MockObject $mock */
    $mock = Stub::makeEmpty(SegmentSubscribersRepository::class, ['getSubscriberIdsInSegment']);
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue([$this->subscriber2->getId()]));
    $this->segment3->setType(SegmentEntity::TYPE_DYNAMIC);

    $finder = new SubscribersFinder($mock, $this->segmentsRepository);
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->sending->task(),
      [
        $this->segment1->getId(),
        $this->segment2->getId(),
        $this->segment3->getId(),
      ]
    );

    expect($subscribersCount)->equals(1);
    expect($this->sending->getSubscribers())->equals([$this->subscriber2->getId()]);
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
  }
}
