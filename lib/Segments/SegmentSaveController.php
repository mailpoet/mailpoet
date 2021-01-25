<?php

namespace MailPoet\Segments;

use InvalidArgumentException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    EntityManager $entityManager
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
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

    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['segment' => $segmentEntity]);

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($duplicate, $subscriberSegments) {
      $entityManager->persist($duplicate);
      $entityManager->flush();

      foreach ($subscriberSegments as $subscriberSegment) {
        $subscriber = $subscriberSegment->getSubscriber();
        if (!$subscriber) {
          continue;
        }
        $subscriberDuplicate = new SubscriberSegmentEntity(
          $duplicate,
          $subscriber,
          $subscriberSegment->getStatus()
        );
        $entityManager->persist($subscriberDuplicate);
      }
      $entityManager->flush();
    });

    return $duplicate;
  }

  private function checkSegmenUniqueName(string $name, ?int $id): void {
    if (!$this->segmentsRepository->isNameUnique($name, $id)) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }
}
