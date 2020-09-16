<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="dynamic_segment_filters")
 */
class DynamicSegmentFilterEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use SafeToOneAssociationLoadTrait;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SegmentEntity", inversedBy="filters")
   * @var SegmentEntity|null
   */
  private $segment;

  /**
   * @ORM\Column(type="serialized_array")
   * @var array|null
   */
  private $filterData;

  public function __construct(SegmentEntity $segment, array $filterData) {
    $this->segment = $segment;
    $this->filterData = $filterData;
  }

  /**
   * @return SegmentEntity|null
   */
  public function getSegment() {
    $this->safelyLoadToOneAssociation('segment');
    return $this->segment;
  }

  /**
   * @return array|null
   */
  public function getFilterData() {
    return $this->filterData;
  }

  /**
   * @return mixed|null
   */
  public function getFilterDataParam(string $name) {
    return $this->filterData[$name] ?? null;
  }

  /**
   * @return string|null
   */
  public function getSegmentType() {
    $filterData = $this->getFilterData();
    return $filterData['segmentType'] ?? null;
  }

  public function setSegment(SegmentEntity $segment) {
    $this->segment = $segment;
  }

  public function setFilterData(array $filterData) {
    $this->filterData = $filterData;
  }
}
