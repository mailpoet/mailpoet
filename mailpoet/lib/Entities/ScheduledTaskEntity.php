<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Collection;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="scheduled_tasks")
 */
class ScheduledTaskEntity {
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_CANCELLED = 'cancelled';
  const STATUS_PAUSED = 'paused';
  const STATUS_INVALID = 'invalid';
  const STATUS_CLI = 'cli'; // A task claimed and run by a WP-CLI process; hidden from the web daemon, which only queries known statuses (null, scheduled).
  const VIRTUAL_STATUS_RUNNING = 'running'; // For historical reasons this is stored as null in DB
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;
  const BASIC_RESCHEDULE_TIMEOUT = 5; // minutes
  const MAX_RESCHEDULE_TIMEOUT = 1440; // minutes

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;
  use SafeToOneAssociationLoadTrait;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $type;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $status;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $priority = 0;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $scheduledAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $cancelledAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $processedAt;

  /**
   * @ORM\Column(type="json", nullable=true)
   * @var array|null
   */
  private $meta;

  /**
   * @ORM\Column(type="boolean", nullable=true)
   * @var bool|null
   */
  private $inProgress;

  /**
   * @ORM\Column(type="integer", options={"default" : 0})
   * @var int
   */
  private $rescheduleCount = 0;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\ScheduledTaskSubscriberEntity", mappedBy="task", fetch="EXTRA_LAZY")
   * @var Collection<int, ScheduledTaskSubscriberEntity>
   */
  private $subscribers;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity", mappedBy="task", fetch="EXTRA_LAZY")
   * @var Collection<int, ScheduledTaskQueuedSubscriberEntity>
   */
  private $queuedSubscribers;

  /**
   * @ORM\OneToOne(targetEntity="MailPoet\Entities\SendingQueueEntity", mappedBy="task", fetch="EAGER")
   * @var SendingQueueEntity|null
   */
  private $sendingQueue;

  public function __construct() {
    $this->subscribers = new ArrayCollection();
    $this->queuedSubscribers = new ArrayCollection();
  }

  /**
   * @return string|null
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @param string|null $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * @return string|null
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param string|null $status
   */
  public function setStatus($status) {
    if ($status === self::VIRTUAL_STATUS_RUNNING) {
      $status = null;
    }
    $this->status = $status;
  }

  /**
   * @return int
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * @param int $priority
   */
  public function setPriority($priority) {
    $this->priority = $priority;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getScheduledAt() {
    return $this->scheduledAt;
  }

  /**
   * @param DateTimeInterface|null $scheduledAt
   */
  public function setScheduledAt($scheduledAt) {
    $this->scheduledAt = $scheduledAt;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getCancelledAt() {
    return $this->cancelledAt;
  }

  /**
   * @param DateTimeInterface|null $cancelledAt
   */
  public function setCancelledAt($cancelledAt) {
    $this->cancelledAt = $cancelledAt;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getProcessedAt() {
    return $this->processedAt;
  }

  /**
   * @param DateTimeInterface|null $processedAt
   */
  public function setProcessedAt($processedAt) {
    $this->processedAt = $processedAt;
  }

  /**
   * @return array|null
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * @param array|null $meta
   */
  public function setMeta($meta) {
    $this->meta = $meta;
  }

  /**
   * @return bool|null
   */
  public function getInProgress() {
    return $this->inProgress;
  }

  /**
   * @param bool|null $inProgress
   */
  public function setInProgress($inProgress) {
    $this->inProgress = $inProgress;
  }

  public function getRescheduleCount(): int {
    return $this->rescheduleCount;
  }

  public function setRescheduleCount(int $rescheduleCount) {
    $this->rescheduleCount = $rescheduleCount;
  }

  /**
   * @return Collection<int, ScheduledTaskSubscriberEntity>
   */
  public function getSubscribers(): Collection {
    return $this->subscribers;
  }

  /**
   * Pending recipients of an in-flight task that uses subscriber queue/log storage.
   *
   * @return Collection<int, ScheduledTaskQueuedSubscriberEntity>
   */
  public function getQueuedSubscribers(): Collection {
    return $this->queuedSubscribers;
  }

  /**
   * Total recipients across the queue (pending) and the log (processed).
   * Both collections are EXTRA_LAZY, so this is two COUNT queries, no hydration.
   */
  public function getTotalSubscribersCount(): int {
    return $this->subscribers->count() + $this->queuedSubscribers->count();
  }

  /**
   * Number of pending recipients in the queue. The collection is EXTRA_LAZY, so
   * this is a single COUNT query and never hydrates the (possibly huge) set.
   * Only accurate for a managed (DB-loaded) task.
   */
  public function getQueuedCount(): int {
    return $this->queuedSubscribers->count();
  }

  /**
   * First pending recipient (lowest subscriber id), or null if the queue is empty.
   * Criteria matching on the EXTRA_LAZY collection runs a single
   * `ORDER BY subscriber_id LIMIT 1` query instead of hydrating the queue.
   * Only accurate for a managed (DB-loaded) task.
   */
  public function getFirstQueuedSubscriber(): ?SubscriberEntity {
    $criteria = Criteria::create()
      ->orderBy(['subscriber' => 'ASC'])
      ->setMaxResults(1);
    $first = $this->queuedSubscribers->matching($criteria)->first();
    return ($first instanceof ScheduledTaskQueuedSubscriberEntity) ? $first->getSubscriber() : null;
  }

  /**
   * @param int $processed ScheduledTaskSubscriberEntity::PROCESSED_* constant
   * @return SubscriberEntity[]
   */
  public function getSubscribersByProcessed(int $processed): array {
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq('processed', $processed));
    $subscribers = $this->subscribers->matching($criteria)->map(function (?ScheduledTaskSubscriberEntity $taskSubscriber = null): ?SubscriberEntity {
      if (!$taskSubscriber) return null;
      return $taskSubscriber->getSubscriber();
    });
    return array_filter($subscribers->toArray());
  }

  public function getSendingQueue(): ?SendingQueueEntity {
    $this->safelyLoadToOneAssociation('sendingQueue');
    return $this->sendingQueue;
  }

  public function setSendingQueue(SendingQueueEntity $sendingQueue): void {
    $this->sendingQueue = $sendingQueue;
  }
}
