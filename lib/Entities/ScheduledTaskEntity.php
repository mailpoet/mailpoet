<?php

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="scheduled_tasks")
 */
class ScheduledTaskEntity {
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const VIRTUAL_STATUS_RUNNING = 'running'; // For historical reasons this is stored as null in DB
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  /**
   * @Column(type="string")
   * @var string|null
   */
  private $type;

  /**
   * @Column(type="string")
   * @var string|null
   */
  private $status;

  /**
   * @Column(type="integer")
   * @var int
   */
  private $priority = 0;

  /**
   * @Column(type="datetimetz")
   * @var DateTimeInterface|null
   */
  private $scheduled_at;

  /**
   * @Column(type="datetimetz")
   * @var DateTimeInterface|null
   */
  private $processed_at;

  /**
   * @Column(type="json")
   * @var array|null
   */
  private $meta;

  /**
   * @return string|null
   */
  function getType() {
    return $this->type;
  }

  /**
   * @param string|null $type
   */
  function setType($type) {
    $this->type = $type;
  }

  /**
   * @return string|null
   */
  function getStatus() {
    return $this->status;
  }

  /**
   * @param string|null $status
   */
  function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return int
   */
  function getPriority() {
    return $this->priority;
  }

  /**
   * @param int $priority
   */
  function setPriority($priority) {
    $this->priority = $priority;
  }

  /**
   * @return DateTimeInterface|null
   */
  function getScheduledAt() {
    return $this->scheduled_at;
  }

  /**
   * @param DateTimeInterface|null $scheduled_at
   */
  function setScheduledAt($scheduled_at) {
    $this->scheduled_at = $scheduled_at;
  }

  /**
   * @return DateTimeInterface|null
   */
  function getProcessedAt() {
    return $this->processed_at;
  }

  /**
   * @param DateTimeInterface|null $processed_at
   */
  function setProcessedAt($processed_at) {
    $this->processed_at = $processed_at;
  }

  /**
   * @return array|null
   */
  function getMeta() {
    return $this->meta;
  }

  /**
   * @param array|null $meta
   */
  function setMeta($meta) {
    $this->meta = $meta;
  }
}
