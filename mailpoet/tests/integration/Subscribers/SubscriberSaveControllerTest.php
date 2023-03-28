<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\ConflictException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoetVendor\Carbon\Carbon;

class SubscriberSaveControllerTest extends \MailPoetTest {
  /** @var SubscriberSaveController */
  private $saveController;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function _before() {
    parent::_before();
    $this->saveController = $this->diContainer->get(SubscriberSaveController::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
  }

  public function testItCreatesNewSubscriber(): void {
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $segmentTwo = $this->segmentsRepository->createOrUpdate('Segment Two');
    $data = [
      'email' => 'first@test.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $segmentOne->getId(),
        $segmentTwo->getId(),
      ],
      'created_at' => '2020-04-30 13:14:15',
      'confirmed_at' => '2020-04-31 13:14:15',
      'confirmed_ip' => '192.168.1.32',
      'subscribed_ip' => '192.168.1.16',
      'wp_user_id' => 7,
      'tags' => [
        'First',
        'Second',
      ],
    ];

    $subscriber = $this->saveController->save($data);
    expect($subscriber->getEmail())->equals($data['email']);
    expect($subscriber->getStatus())->equals($data['status']);
    expect($subscriber->getFirstName())->equals($data['first_name']);
    expect($subscriber->getLastName())->equals($data['last_name']);
    expect($subscriber->getCreatedAt())->equals(Carbon::createFromFormat('Y-m-d H:i:s', $data['created_at']));
    expect($subscriber->getConfirmedAt())->equals(Carbon::createFromFormat('Y-m-d H:i:s', $data['confirmed_at']));
    expect($subscriber->getConfirmedIp())->equals($data['confirmed_ip']);
    expect($subscriber->getSubscribedIp())->equals($data['subscribed_ip']);
    expect($subscriber->getWpUserId())->equals($data['wp_user_id']);
    expect($subscriber->getUnsubscribeToken())->notNull();
    expect($subscriber->getLinkToken())->notNull();
    expect($subscriber->getId())->notNull();
    expect($subscriber->getLastSubscribedAt())->notNull();
    expect($subscriber->getSegments())->count(2);
    expect($subscriber->getSubscriberSegments())->count(2);
    expect($subscriber->getSubscriberTags())->count(2);
  }

  public function testItCanUpdateASubscriber(): void {
    $subscriber = $this->createSubscriber('second@test.com', SubscriberEntity::STATUS_UNCONFIRMED);
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $data = [
      'id' => $subscriber->getId(),
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $segmentOne->getId(),
      ],
      'tags' => [
        'First',
      ],
    ];

    $this->entityManager->clear();
    $subscriber = $this->saveController->save($data);
    expect($subscriber->getEmail())->equals('second@test.com');
    expect($subscriber->getStatus())->equals($data['status']);
    expect($subscriber->getFirstName())->equals($data['first_name']);
    expect($subscriber->getLastName())->equals($data['last_name']);
    expect($subscriber->getLastSubscribedAt())->notNull();
    expect($subscriber->getSegments())->count(1);
    expect($subscriber->getSubscriberSegments())->count(1);
    expect($subscriber->getSubscriberTags())->count(1);
    // Check exact tag name
    $tagNames = array_values(array_map(function (SubscriberTagEntity $subscriberTag): string {
      return ($tag = $subscriberTag->getTag()) ? $tag->getName() : '';
    }, $subscriber->getSubscriberTags()->toArray()));
    expect($data['tags'])->equals($tagNames);

    // Test updating tags
    $data['tags'] = [
      'Second',
      'Third',
    ];
    $subscriber = $this->saveController->save($data);
    expect($subscriber->getSubscriberTags())->count(2);
    $tagNames = array_values(array_map(function (SubscriberTagEntity $subscriberTag): string {
      return ($tag = $subscriberTag->getTag()) ? $tag->getName() : '';
    }, $subscriber->getSubscriberTags()->toArray()));
    expect($data['tags'])->equals($tagNames);
  }

  public function testItThrowsExceptionWhenUpdatingSubscriberEmailIfNotUnique(): void {
    $subscriber = $this->createSubscriber('second@test.com', SubscriberEntity::STATUS_UNCONFIRMED);
    $subscriber2 = $this->createSubscriber('third@test.com', SubscriberEntity::STATUS_UNCONFIRMED);

    $data = [
      'id' => $subscriber->getId(),
      'email' => $subscriber2->getEmail(),
    ];

    $this->entityManager->clear();
    $this->expectException(ConflictException::class);
    $this->expectExceptionMessage('A subscriber with E-mail "' . $subscriber2->getEmail() . '" already exists.');

    $this->saveController->save($data);
  }

  public function testItDeletesOrphanSubscriberSegmentsOnUpdate(): void {
    $subscriber = $this->createSubscriber('second@test.com', SubscriberEntity::STATUS_UNCONFIRMED);
    $segmentOne = $this->segmentsRepository->createOrUpdate('Segment One');
    $segmentTwo = $this->segmentsRepository->createOrUpdate('Segment Two');

    // Create orphan record on SubscriberSegments
    $orphanSegment = $this->segmentsRepository->createOrUpdate('Orphan');
    $this->createSubscriberSegment($subscriber, $orphanSegment);
    $this->entityManager->remove($orphanSegment);
    $this->entityManager->flush();
    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['subscriber' => $subscriber]);
    expect($subscriberSegments)->count(1);

    // Update subscriber with new segments
    $data = [
      'id' => $subscriber->getId(),
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [
        $segmentOne->getId(),
        $segmentTwo->getId(),
      ],
    ];

    $this->entityManager->clear();
    $subscriber = $this->saveController->save($data);
    // Check the $orphanSegment is gone
    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['subscriber' => $subscriber]);
    expect($subscriberSegments)->count(2);
  }

  private function createSubscriber(string $email, string $status): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSubscriberSegment(SubscriberEntity $subscriber, SegmentEntity $segment): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }
}
