<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use DateTimeImmutable;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Models\CustomField;

class SubscribersRepositoryTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $repository;
  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;
  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
  }

  public function testItBulkTrashSubscribers(): void {
    $subscriberOne = $this->createSubscriber('one@trash.com');
    $subscriberTwo = $this->createSubscriber('two@trash.com');
    $subscriberThree = $this->createSubscriber('three@trash.com');

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $subscriberThreeId = $subscriberThree->getId();

    $this->repository->bulkTrash([
      $subscriberOneId,
      $subscriberTwoId,
    ]);

    $this->entityManager->clear();

    // trashed subscriber
    $subscriberOne = $this->repository->findOneById($subscriberOneId);
    assert($subscriberOne instanceof SubscriberEntity);
    expect($subscriberOne->getDeletedAt())->notNull();
    $subscriberTwo = $this->repository->findOneById($subscriberOneId);
    assert($subscriberTwo instanceof SubscriberEntity);
    expect($subscriberTwo->getDeletedAt())->notNull();
    // don't trashed subscriber
    $subscriberThree = $this->repository->findOneById($subscriberThreeId);
    assert($subscriberThree instanceof SubscriberEntity);
    expect($subscriberThree->getDeletedAt())->null();
  }

  public function testItBulkRestoreTrashedSubscribers(): void {
    $subscriberOne = $this->createSubscriber('one@restore.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@restore.com', new DateTimeImmutable());

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();

    $this->repository->bulkRestore([
      $subscriberOneId,
    ]);

    $this->entityManager->clear();

    // restored subscriber
    $subscriberOne = $this->repository->findOneById($subscriberOneId);
    assert($subscriberOne instanceof SubscriberEntity);
    expect($subscriberOne->getDeletedAt())->null();
    // don't restored subscriber
    $subscriberTwo = $this->repository->findOneById($subscriberTwoId);
    assert($subscriberTwo instanceof SubscriberEntity);
    expect($subscriberTwo->getDeletedAt())->notNull();
  }

  public function testItBulkDeleteSubscribers(): void {
    $subscriberOne = $this->createSubscriber('one@delete.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@delete.com', new DateTimeImmutable());
    $segmentOne = $this->createSegment('One Delete');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentOne, $subscriberTwo);
    $customField = $this->createCustomField('CF');
    $this->createSubscriberCustomField($subscriberOne, $customField);
    $this->createSubscriberCustomField($subscriberTwo, $customField);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();

    $this->repository->bulkDelete([
      $subscriberOneId,
    ]);

    $this->entityManager->clear();

    // deleted subscriber
    expect($this->repository->findOneById($subscriberOneId))->null();
    expect($this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriberOneId]))->null();
    expect($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $subscriberOneId]))->null();
    // don't restored subscriber
    $subscriberTwo = $this->repository->findOneById($subscriberTwoId);
    assert($subscriberTwo instanceof SubscriberEntity);
    expect($subscriberTwo->getDeletedAt())->notNull(); 
    expect($this->subscriberSegmentRepository->findOneBy(['subscriber' => $subscriberTwoId]))->notNull();
    expect($this->subscriberCustomFieldRepository->findOneBy(['subscriber' => $subscriberTwoId]))->notNull();
  }

  public function testItBulkRemoveSubscribersFromSegment(): void {
    $subscriberOne = $this->createSubscriber('one@remove.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@remove.com', new DateTimeImmutable());
    $segmentOne = $this->createSegment('One Remove');
    $segmentTwo = $this->createSegment('Two Remove');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentOne, $subscriberTwo);
    $this->createSubscriberSegment($segmentTwo, $subscriberOne);
    $this->createSubscriberSegment($segmentTwo, $subscriberTwo);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();
    $segmentTwoId = $segmentTwo->getId();

    $this->repository->bulkRemoveFromSegment($segmentOne, [$subscriberOneId]);
    $this->repository->bulkRemoveFromSegment($segmentTwo, [$subscriberTwoId]);

    $this->entityManager->clear();

    // subscriber with removed segment one
    expect($this->repository->findOneById($subscriberOneId))->notNull();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentOneId,
    ]))->null();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentTwoId,
    ]))->notNull();

    // subscriber with removed segment two
    expect($this->repository->findOneById($subscriberTwoId))->notNull(); 
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentTwoId,
    ]))->null();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentOneId,
    ]))->notNull();
  }

  public function testItBulkRemoveSubscriberFromAllSegments(): void {
    $subscriberOne = $this->createSubscriber('one@removeAll.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@removeAll.com', new DateTimeImmutable());
    $segmentOne = $this->createSegment('One Remove All');
    $segmentTwo = $this->createSegment('Two Remove All');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentOne, $subscriberTwo);
    $this->createSubscriberSegment($segmentTwo, $subscriberOne);
    $this->createSubscriberSegment($segmentTwo, $subscriberTwo);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();
    $segmentTwoId = $segmentTwo->getId();

    $this->repository->bulkRemoveFromAllSegments([$subscriberOneId]);

    $this->entityManager->clear();

    // subscriber with removed segments
    expect($this->repository->findOneById($subscriberOneId))->notNull();
    expect($this->subscriberSegmentRepository->findBy(['subscriber' => $subscriberOneId]))->count(0);

    // subscriber with segments
    expect($this->repository->findOneById($subscriberTwoId))->notNull(); 
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentOneId,
    ]))->notNull();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentTwoId,
    ]))->notNull();
  }

  public function testItBulkAddSubscribersToSegment(): void {
    $subscriberOne = $this->createSubscriber('one@add.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@add.com', new DateTimeImmutable());
    $segmentOne = $this->createSegment('One Add');

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();

    $this->repository->bulkAddToSegment($segmentOne, [$subscriberOneId]);

    $this->entityManager->clear();

    // subscriber with segment
    expect($this->repository->findOneById($subscriberOneId))->notNull();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentOneId,
    ]))->notNull();

    // subscriber without segment
    expect($this->repository->findOneById($subscriberTwoId))->notNull(); 
    expect($this->subscriberSegmentRepository->findBy(['subscriber' => $subscriberTwoId]))->count(0);
  }

  public function testItBulMoveSubscribersToSegment(): void {
    $subscriberOne = $this->createSubscriber('one@move.com', new DateTimeImmutable());
    $subscriberTwo = $this->createSubscriber('two@move.com', new DateTimeImmutable());
    $segmentOne = $this->createSegment('One Move');
    $segmentTwo = $this->createSegment('Two Move');
    $this->createSubscriberSegment($segmentOne, $subscriberOne);
    $this->createSubscriberSegment($segmentTwo, $subscriberTwo);

    $subscriberOneId = $subscriberOne->getId();
    $subscriberTwoId = $subscriberTwo->getId();
    $segmentOneId = $segmentOne->getId();
    $segmentTwoId = $segmentTwo->getId();

    $this->repository->bulkMoveToSegment($segmentTwo, [$subscriberOneId]);

    $this->entityManager->clear();

    // subscriber moved to segment two
    expect($this->repository->findOneById($subscriberOneId))->notNull();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentOneId,
    ]))->null();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberOneId,
      'segment' => $segmentTwoId,
    ]))->notNull();

    // subscriber which stay in segment two
    expect($this->repository->findOneById($subscriberTwoId))->notNull(); 
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentOneId,
    ]))->null();
    expect($this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriberTwoId,
      'segment' => $segmentTwoId,
    ]))->notNull();
  }

  public function testItDoesntRemovePermanentlyWordpressSubscriber(): void {
    $subscriber = $this->createSubscriber('wpsubscriber@delete.com');
    $subscriber->setWpUserId(1);
    $this->repository->flush();
    $this->entityManager->clear();
    $subscriberId = $subscriber->getId();

    $count = $this->repository->bulkDelete([$subscriber->getId()]);

    expect($count)->equals(0);
    expect($this->repository->findOneById($subscriberId))->notNull();
  }

  public function testItDoesntRemovePermanentlyWoocommerceSubscriber(): void {
    $subscriber = $this->createSubscriber('wcsubscriber@delete.com');
    $subscriber->setIsWoocommerceUser(true);
    $this->repository->flush();
    $this->entityManager->clear();
    $subscriberId = $subscriber->getId();

    $count = $this->repository->bulkDelete([$subscriberId]);

    expect($count)->equals(0);
    expect($this->repository->findOneById($subscriberId))->notNull();
  }

  private function createSubscriber(string $email, ?DateTimeImmutable $deletedAt = null): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName('John');
    $subscriber->setLastName('Doe');
    $subscriber->setDeletedAt($deletedAt);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, 'some description');
    $this->entityManager->persist($segment);
    $this->entityManager->flush();
    return $segment;
  }

  private function createSubscriberSegment(SegmentEntity $segment, SubscriberEntity $subscriber): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }

  private function createCustomField(string $name): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setName($name);
    $customField->setType(CustomField::TYPE_TEXT);
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    return $customField;
  }

  private function createSubscriberCustomField(SubscriberEntity $subscriber, CustomFieldEntity $customField): SubscriberCustomFieldEntity {
    $subscirberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, 'some value');
    $this->entityManager->persist($subscirberCustomField);
    $this->entityManager->flush();
    return $subscirberCustomField;
  }

  private function cleanup() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
  }
}
