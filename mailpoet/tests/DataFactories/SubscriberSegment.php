<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscriberSegment {
  protected $data;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SegmentEntity */
  private $segment;

  public function __construct(
    SubscriberEntity $subscriber,
    SegmentEntity $segment,
    string $status = SubscriberEntity::STATUS_SUBSCRIBED
  ) {
    $this->subscriber = $subscriber;
    $this->segment = $segment;
    $this->data['status'] = $status;
  }

  public function withStatus(string $status): self {
    $this->data['status'] = $status;
    return $this;
  }

  public function withUpdatedAt(\DateTimeInterface $updatedAt): self {
    $this->data['updatedAt'] = $updatedAt;
    return $this;
  }

  public function create(): SubscriberSegmentEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $entity = new SubscriberSegmentEntity($this->segment, $this->subscriber, $this->data['status']);

    if (isset($this->data['status'])) {
      $entity->setStatus($this->data['status']);
    }

    $entityManager->persist($entity);
    $entityManager->flush();
    $entityManager->refresh($entity);
    if (($this->data['updatedAt'] ?? null) instanceof \DateTimeInterface) {
      $subscribersSegmentsTable = $entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $entityManager->getConnection()->executeQuery("UPDATE {$subscribersSegmentsTable} SET updated_at = :updatedAt WHERE id = :id", [
        'updatedAt' => $this->data['updatedAt']->format('Y-m-d H:i:s'),
        'id' => $entity->getId(),
      ]);
    };
    $entityManager->refresh($entity);
    return $entity;
  }
}
