<?php

namespace MailPoet\Segments;

use InvalidArgumentException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Subscribers\SubscriberSegmentRepository;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
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

    $this->segmentsRepository->persist($duplicate);
    $this->segmentsRepository->flush();
    
    $subscriberSegments = $this->subscriberSegmentRepository->findBy(['segment' => $segmentEntity]);
    foreach ($subscriberSegments as $subscriberSegment) {
      $subscriber = $subscriberSegment->getSubscriber();
      if (!$subscriber) {
        continue;
      }
      $subscriberDuplicate = new SubscriberSegmentEntity(
        $duplicate,
        $subscriber,
        SubscriberEntity::STATUS_SUBSCRIBED
      );
      $this->subscriberSegmentRepository->persist($subscriberDuplicate);
    }
    $this->subscriberSegmentRepository->flush();

    return $duplicate;
  }

  private function checkSegmenUniqueName(string $name, ?int $id): void {
    if (!$this->segmentsRepository->isNameUnique($name, $id)) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }
}
