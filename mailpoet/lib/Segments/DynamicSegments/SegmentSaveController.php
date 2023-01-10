<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\ConflictException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\NotFoundException;
use MailPoet\Segments\SegmentsRepository;
use MailPoetVendor\Doctrine\ORM\ORMException;

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

  /**
   * @throws ConflictException
   * @throws NotFoundException
   * @throws Exceptions\InvalidFilterException
   * @throws ORMException
   */
  public function save(array $data = []): SegmentEntity {
    $id = isset($data['id']) ? (int)$data['id'] : null;
    $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $description = isset($data['description']) ? sanitize_textarea_field($data['description']) : '';
    $filtersData = $this->filterDataMapper->map($data);

    return $this->segmentsRepository->createOrUpdate($name, $description, SegmentEntity::TYPE_DYNAMIC, $filtersData, $id);
  }
}
