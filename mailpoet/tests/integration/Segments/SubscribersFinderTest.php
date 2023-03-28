<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use Codeception\Util\Stub;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

class SubscribersFinderTest extends \MailPoetTest {
  public $scheduledTask;
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

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

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

    $scheduledTaskFactory = new ScheduledTaskFactory();
    $this->scheduledTask = $scheduledTaskFactory->create(SendingTask::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED, new Carbon());
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersFinder = $this->diContainer->get(SubscribersFinder::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
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

    $finder = new SubscribersFinder($mock, $this->segmentsRepository, $this->entityManager);
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

    $finder = new SubscribersFinder($mock, $this->segmentsRepository, $this->entityManager);
    $subscribers = $finder->findSubscribersInSegments([$this->subscriber3->getId()], [$this->segment3->getId(), $this->segment3->getId()]);
    expect($subscribers)->count(1);
  }

  public function testItAddsSubscribersToTaskFromStaticSegments() {
    $subscribersCount = $this->subscribersFinder->addSubscribersToTaskFromSegments(
      $this->scheduledTask,
      [
        $this->segment1->getId(),
        $this->segment2->getId(),
      ]
    );
    expect($subscribersCount)->equals(1);
    $subscribersIds = $this->getScheduledTasksSubscribers($this->scheduledTask->getId());
    expect($subscribersIds)->equals([$this->subscriber2->getId()]);
  }

  public function testItDoesNotAddSubscribersToTaskFromNoSegment() {
    $this->segment3->setType('Invalid type');
    $subscribersCount = $this->subscribersFinder->addSubscribersToTaskFromSegments(
      $this->scheduledTask,
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

    $finder = new SubscribersFinder($mock, $this->segmentsRepository, $this->entityManager);
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->scheduledTask,
      [
        $this->segment2->getId(),
      ]
    );
    expect($subscribersCount)->equals(1);
    $subscribersIds = $this->getScheduledTasksSubscribers($this->scheduledTask->getId());
    expect($subscribersIds)->equals([$this->subscriber1->getId()]);
  }

  public function testItAddsSubscribersToTaskFromStaticAndDynamicSegments() {
    /** @var SegmentSubscribersRepository & MockObject $mock */
    $mock = Stub::makeEmpty(SegmentSubscribersRepository::class, ['getSubscriberIdsInSegment']);
    $mock
      ->expects($this->once())
      ->method('getSubscriberIdsInSegment')
      ->will($this->returnValue([$this->subscriber2->getId()]));
    $this->segment3->setType(SegmentEntity::TYPE_DYNAMIC);

    $finder = new SubscribersFinder($mock, $this->segmentsRepository, $this->entityManager);
    $subscribersCount = $finder->addSubscribersToTaskFromSegments(
      $this->scheduledTask,
      [
        $this->segment1->getId(),
        $this->segment2->getId(),
        $this->segment3->getId(),
      ]
    );

    expect($subscribersCount)->equals(1);
    $subscribersIds = $this->getScheduledTasksSubscribers($this->scheduledTask->getId());
    expect($subscribersIds)->equals([$this->subscriber2->getId()]);
  }

  private function getScheduledTasksSubscribers(int $taskId): array {
    $scheduledTaskSubscribers = $this->scheduledTaskSubscribersRepository->findBy(['task' => $taskId]);
    $subscribersIds = array_map(function($scheduledTaskSubscriber) {
      $subscriber = $scheduledTaskSubscriber->getSubscriber();

      if ($subscriber instanceof SubscriberEntity) {
        return $subscriber->getId();
      }
    }, $scheduledTaskSubscribers);

    return $subscribersIds;
  }
}
