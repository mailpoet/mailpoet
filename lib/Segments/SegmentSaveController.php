<?php

namespace MailPoet\Segments;

use InvalidArgumentException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\NotFoundException;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
  }

  public function save(array $data = []): SegmentEntity {
    $this->checkSegmenUniqueName($data['name'] ?? '');

    $segment = $this->createOrUpdateSegment($data);
    return $segment;
  }

  private function checkSegmenUniqueName(string $name): void {
    $segment = $this->segmentsRepository->findOneBy(['name' => $name]);
    if ($segment) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }

  private function createOrUpdateSegment(array $data): SegmentEntity {
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    if (isset($data['id'])) {
      $segment = $this->getSegment((int)$data['id']);
      $segment->setName($name);
      $segment->setDescription($description);
    } else {
      $segment = new SegmentEntity(
        $name,
        SegmentEntity::TYPE_DEFAULT,
        $description
      );
      $this->segmentsRepository->persist($segment);
    }

    $this->segmentsRepository->flush();
    return $segment;
  }

  private function getSegment(int $segmentId): SegmentEntity {
    $segment = $this->segmentsRepository->findOneById($segmentId);
    if (!$segment) {
      throw new NotFoundException();
    }

    return $segment;
  }
}
