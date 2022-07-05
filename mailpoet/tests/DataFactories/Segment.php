<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Segment {

  /** @var array */
  protected $data;

  /** @var EntityManager */
  protected $entityManager;

  /** @var SegmentsRepository */
  protected $segmentsRepository;

  public function __construct() {
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $this->segmentsRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->data = [
      'type' => SegmentEntity::TYPE_DEFAULT,
      'name' => 'List ' . bin2hex(random_bytes(7)), // phpcs:ignore
      'description' => '',
    ];
  }

  /**
   * @return $this
   */
  public function withName(string $name) {
    return $this->update('name', $name);
  }

  /**
   * @return $this
   */
  public function withDescription(string $description) {
    return $this->update('description', $description);
  }

  /**
   * @return $this
   */
  public function withDeleted() {
    return $this->update('deleted_at', Carbon::now());
  }

  /**
   * @return $this
   */
  public function withType(string $type) {
    return $this->update('type', $type);
  }

  public function create(): SegmentEntity {
    $segment = $this->segmentsRepository->createOrUpdate(
      $this->data['name'],
      $this->data['description'],
      $this->data['type']
    );
    if (($this->data['deleted_at'] ?? null) instanceof \DateTimeInterface) {
      $segment->setDeletedAt($this->data['deleted_at']);
      $this->entityManager->flush();
    }
    return $segment;
  }

  /**
   * @return $this
   */
  private function update(string $item, $value) {
    $data = $this->data;
    $data[$item] = $value;
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
