<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class SubscriberSaveControllerTest extends \MailPoetTest {
  /** @var SubscriberSaveController */
  private $saveController;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->saveController = $this->diContainer->get(SubscriberSaveController::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
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
    ];

    $subscriber = $this->saveController->save($data);
    expect($subscriber->getEmail())->equals($data['email']);
    expect($subscriber->getStatus())->equals($data['status']);
    expect($subscriber->getFirstName())->equals($data['first_name']);
    expect($subscriber->getLastName())->equals($data['last_name']);
    expect($subscriber->getUnsubscribeToken())->notNull();
    expect($subscriber->getLinkToken())->notNull();
    expect($subscriber->getId())->notNull();
    expect($subscriber->getLastSubscribedAt())->notNull();
    expect($subscriber->getSegments())->count(2);
    expect($subscriber->getSubscriberSegments())->count(2);
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
  }

  public function _after(): void {
    $this->cleanup();
  }

  private function createSubscriber(string $email, string $status): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function cleanup(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
  }
}
