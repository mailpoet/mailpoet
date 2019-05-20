<?php

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Models\Subscriber;

/**
 * Gets batches of subscribers for export.
 */
abstract class SubscribersGetter {

  protected $segments_ids;
  protected $batch_size;
  protected $offset;
  protected $finished;

  public function __construct($segments_ids, $batch_size) {
    $this->segments_ids = $segments_ids;
    $this->batch_size = $batch_size;
    $this->reset();
  }

  public function reset() {
    $this->offset = 0;
    $this->finished = false;
  }

  /**
   * Initialize the query by selecting fields and ignoring trashed subscribers.
   *
   * @return \ORM
   */
  protected function select() {
    return Subscriber::selectMany(
      'first_name',
      'last_name',
      'email',
      'subscribed_ip',
      [
        'global_status' => Subscriber::$_table . '.status',
      ]
    )
    ->filter('filterWithCustomFieldsForExport')
    ->groupBy(Subscriber::$_table . '.id')
    ->whereNull(Subscriber::$_table . '.deleted_at');
  }

  /**
   * Filters the subscribers query based on the segments, offset and batch size.
   *
   * @param  \ORM $subscribers
   * @return array
   */
  abstract protected function filter($subscribers);

  /**
   * Gets the next batch of subscribers or `false` if no more!
   */
  public function get() {
    if ($this->finished) {
      return false;
    }

    $subscribers = $this->select();
    $subscribers = $this->filter($subscribers);

    $this->offset += $this->batch_size;

    if (count($subscribers) < $this->batch_size) {
      $this->finished = true;
    }

    return $subscribers;
  }
}
