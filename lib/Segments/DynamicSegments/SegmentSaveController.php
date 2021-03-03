<?php

namespace MailPoet\Segments\DynamicSegments;

use InvalidArgumentException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class SegmentSaveController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var FilterDataMapper */
  private $filterDataMapper;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    FilterDataMapper $filterDataMapper
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->filterDataMapper = $filterDataMapper;
  }

  public function save(array $data = []): SegmentEntity {
    $id = isset($data['id']) ? (int)$data['id'] : null;
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $filterData = $this->filterDataMapper->map($data);

    $this->checkSegmentUniqueName($name, $id);

    return $this->segmentsRepository->createOrUpdate($name, $description, SegmentEntity::TYPE_DYNAMIC, $filterData, $id);
  }

  private function checkSegmentUniqueName(string $name, ?int $id): void {
    if (!$this->segmentsRepository->isNameUnique($name, $id)) {
      throw new InvalidArgumentException("Segment with name: '{$name}' already exists.");
    }
  }
}
