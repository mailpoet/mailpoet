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
    $this->checkSegmenUniqueName($data['name'] ?? '');

    $id = isset($data['id']) ? (int)$data['id'] : null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';

    return $this->segmentsRepository->createOrUpdate($name, $description, $id);
  }

  private function checkSegmenUniqueName(string $name): void {
    $segment = $this->segmentsRepository->findOneBy(['name' => $name]);
    if ($segment) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }
}
