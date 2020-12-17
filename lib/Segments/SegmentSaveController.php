<?php

namespace MailPoet\Segments;

use InvalidArgumentException;
use MailPoet\Entities\SegmentEntity;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
  }

  public function save(array $data = []): SegmentEntity {
    $id = isset($data['id']) ? (int)$data['id'] : null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    
    $this->checkSegmenUniqueName($name, $id);

    return $this->segmentsRepository->createOrUpdate($name, $description, $id);
  }

  private function checkSegmenUniqueName(string $name, ?int $id): void {
    if (!$this->segmentsRepository->isNameUnique($name, $id)) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }
}
