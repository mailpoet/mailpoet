<?php declare(strict_types = 1);

namespace MailPoet\API\MP\v1;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;

class Segments {
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct (
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
  }

  public function getAll(): array {
    $segments = $this->segmentsRepository->findBy(['type' => SegmentEntity::TYPE_DEFAULT], ['id' => 'asc']);
    $result = [];
    foreach ($segments as $segment) {
      $result[] = $this->buildItem($segment);
    }
    return $result;
  }

  public function addList(array $data): array {
    if (empty($data['name'])) {
      throw new APIException(
        __('List name is required.', 'mailpoet'),
        APIException::LIST_NAME_REQUIRED
      );
    }

    if (!$this->segmentsRepository->isNameUnique($data['name'], null)) {
      throw new APIException(
        __('This list already exists.', 'mailpoet'),
        APIException::LIST_EXISTS
      );
    }

    try {
      $segment = $this->segmentsRepository->createOrUpdate($data['name'], $data['description'] ?? '');
    } catch (\Exception $e) {
      throw new APIException(
       sprintf(__('Failed to add subscriber: %s', 'mailpoet'), $e->getMessage()),
        APIException::FAILED_TO_SAVE_LIST
      );
    }

    return $this->buildItem($segment);
  }

  /**
   * @param SegmentEntity $segment
   * @return array
   */
  private function buildItem(SegmentEntity $segment): array {
    return [
      'id' => (string)$segment->getId(), // (string) for BC
      'name' => $segment->getName(),
      'type' => $segment->getType(),
      'description' => $segment->getDescription(),
      'created_at' => ($createdAt = $segment->getCreatedAt()) ? $createdAt->format(self::DATE_FORMAT) : null,
      'updated_at' => $segment->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $segment->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
    ];
  }
}
