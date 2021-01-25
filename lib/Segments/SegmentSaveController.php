<?php

namespace MailPoet\Segments;

use InvalidArgumentException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    EntityManager $entityManager
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->entityManager = $entityManager;
  }

  public function save(array $data = []): SegmentEntity {
    $id = isset($data['id']) ? (int)$data['id'] : null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    
    $this->checkSegmenUniqueName($name, $id);

    return $this->segmentsRepository->createOrUpdate($name, $description, $id);
  }

  public function duplicate(SegmentEntity $segmentEntity): SegmentEntity {
    $duplicate = clone $segmentEntity;
    $duplicate->setName(sprintf(__('Copy of %s', 'mailpoet'), $segmentEntity->getName()));

    $this->checkSegmenUniqueName($duplicate->getName(), $duplicate->getId());

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($duplicate, $segmentEntity) {
      $entityManager->persist($duplicate);
      $entityManager->flush();

      $subscriberSegmentTable = $entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $conn = $this->entityManager->getConnection();
      $stmt = $conn->prepare("
        INSERT INTO $subscriberSegmentTable (segment_id, subscriber_id, status, created_at)
        SELECT :duplicateId, subscriber_id, status, NOW()
        FROM $subscriberSegmentTable
        WHERE segment_id = :segmentId
      ");
      $stmt->execute([
        'duplicateId' => $duplicate->getId(),
        'segmentId' => $segmentEntity->getId(),
      ]);
    });

    return $duplicate;
  }

  private function checkSegmenUniqueName(string $name, ?int $id): void {
    if (!$this->segmentsRepository->isNameUnique($name, $id)) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }
}
