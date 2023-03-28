<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\ConflictException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Subscribers\SubscriberSegmentRepository;

class SegmentSaveControllerTest extends \MailPoetTest {
  /** @var SegmentSaveController */
  private $saveController;
  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function _before(): void {
    parent::_before();
    $this->saveController = $this->diContainer->get(SegmentSaveController::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
  }

  public function testItCanSaveASegment(): void {
    $segmentData = [
      'name' => 'Segment one',
      'description' => 'Description',
    ];

    $segment = $this->saveController->save($segmentData);
    expect($segment->getName())->equals('Segment one');
    expect($segment->getDescription())->equals('Description');
    expect($segment->getType())->equals(SegmentEntity::TYPE_DEFAULT);
  }

  public function testItDuplicatesSegment(): void {
    $segment = $this->createSegment('Segment two');
    $subscriber1 = $this->createSubscriber('subscribed@mailpoet.com');
    $subscriber2 = $this->createSubscriber('unsubscribed@mailpoet.com');
    $subscriberSegment1 = $this->createSubscriberSegment($subscriber1, $segment, SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriberSegment2 = $this->createSubscriberSegment($subscriber2, $segment, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $duplicate = $this->saveController->duplicate($segment);
    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['segment' => $duplicate]);
    $subscriberDuplicate1 = $this->subscriberSegmentRepository->findOneBy(['segment' => $duplicate, 'subscriber' => $subscriber1]);
    $subscriberDuplicate2 = $this->subscriberSegmentRepository->findOneBy(['segment' => $duplicate, 'subscriber' => $subscriber2]);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberDuplicate1);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberDuplicate2);
    expect($duplicate->getName())->equals('Copy of ' . $segment->getName());
    expect($duplicate->getDescription())->equals($segment->getDescription());
    expect($duplicate->getType())->equals($segment->getType());
    expect($subscriberSegments)->count(2);
    expect($subscriberDuplicate1->getStatus())->equals($subscriberSegment1->getStatus());
    expect($subscriberDuplicate2->getStatus())->equals($subscriberSegment2->getStatus());
  }

  public function testItCheckDuplicateSegment(): void {
    $name = 'Test name';
    $this->createSegment($name);
    $segmentData = [
      'name' => $name,
      'description' => 'Description',
      'filters' => [[
        'segmentType' => SegmentEntity::TYPE_DEFAULT,
        'wordpressRole' => 'editor',
        'action' => UserRole::TYPE,
      ]],
    ];
    $this->expectException(ConflictException::class);
    $this->expectExceptionMessage("Could not create new segment with name [Test name] because a segment with that name already exists.");
    $this->saveController->save($segmentData);
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail($email);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSubscriberSegment(
    SubscriberEntity $subscriber,
    SegmentEntity $segment,
    string $status
  ): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, $status);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }
}
