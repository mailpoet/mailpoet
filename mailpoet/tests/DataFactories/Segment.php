<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentSaveController;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Segment {

  /** @var array */
  protected $data;

  /** @var EntityManager */
  protected $entityManager;

  /** @var SegmentSaveController */
  protected $saveController;

  public function __construct() {
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $this->saveController = ContainerWrapper::getInstance()->get(SegmentSaveController::class);
    $this->data = [
      'name' => 'List ' . bin2hex(random_bytes(7)), // phpcs:ignore
    ];
  }

  /**
   * @param string $name
   * @return $this
   */
  public function withName($name) {
    $this->data['name'] = $name;
    return $this;
  }

  /**
   * @param string $description
   * @return $this
   */
  public function withDescription($description) {
    $this->data['description'] = $description;
    return $this;
  }

  /**
   * @return $this
   */
  public function withDeleted() {
    $this->data['deleted_at'] = Carbon::now();
    return $this;
  }

  public function create(): SegmentEntity {
    $segment = $this->saveController->save($this->data);
    if (($this->data['deleted_at'] ?? null) instanceof \DateTimeInterface) {
      $segment->setDeletedAt($this->data['deleted_at']);
      $this->entityManager->flush();
    }
    return $segment;
  }
}
