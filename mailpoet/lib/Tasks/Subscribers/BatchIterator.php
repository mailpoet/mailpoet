<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Tasks\Subscribers;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Newsletter\Sending\ScheduledTaskQueuedSubscriberRepository;

/**
 * Iterates the pending recipients of a task in id-ordered batches. Pending
 * recipients always live in the queue table (`scheduled_task_queued_subscribers`);
 * once processed they are moved to the log, so the iterator never reads the log.
 *
 * @implements \Iterator<null, array>
 */
class BatchIterator implements \Iterator, \Countable {
  private $taskId;
  private $batchSize;
  private $lastProcessedId = 0;
  private $batchLastId;

  /** @var ScheduledTaskQueuedSubscriberRepository */
  private $scheduledTaskQueuedSubscriberRepository;

  public function __construct(
    $taskId,
    $batchSize
  ) {
    if ($taskId <= 0) {
      throw new \Exception('Task ID must be greater than zero');
    } elseif ($batchSize <= 0) {
      throw new \Exception('Batch size must be greater than zero');
    }
    $this->taskId = (int)$taskId;
    $this->batchSize = (int)$batchSize;
    $this->scheduledTaskQueuedSubscriberRepository = ContainerWrapper::getInstance()->get(ScheduledTaskQueuedSubscriberRepository::class);
  }

  public function rewind(): void {
    $this->lastProcessedId = 0;
  }

  /**
   * @return mixed - it's required for PHP8.1 to prevent using ReturnTypeWillChange that cause an error in PHPStan with PHP7
   */
  #[\ReturnTypeWillChange]
  public function current() {
    $subscribers = $this->scheduledTaskQueuedSubscriberRepository->getSubscriberIdsBatchForTask($this->taskId, $this->lastProcessedId, $this->batchSize);
    $this->batchLastId = end($subscribers);
    return $subscribers;
  }

  /**
   * @return string|float|int|bool|null - it's required for PHP8.1 to prevent using ReturnTypeWillChange that cause an error in PHPStan with PHP7
   */
  #[\ReturnTypeWillChange]
  public function key() {
    return null;
  }

  public function next(): void {
    $this->lastProcessedId = $this->batchLastId;
  }

  public function valid(): bool {
    return $this->count() > 0;
  }

  public function count(): int {
    $count = $this->scheduledTaskQueuedSubscriberRepository->countSubscriberIdsBatchForTask($this->taskId, $this->lastProcessedId);
    return max(0, $count);
  }
}
