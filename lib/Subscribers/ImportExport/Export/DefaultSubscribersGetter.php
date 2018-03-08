<?php

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

/**
 * Gets batches of subscribers from default segments.
 */
class DefaultSubscribersGetter {

  protected $segments_ids;
  protected $batch_size;
  protected $offset;
  protected $finished;
  protected $get_subscribers_without_segment;

  public function __construct($segments_ids, $batch_size) {
    $this->get_subscribers_without_segment = (array_search(0, $segments_ids) !== false);
    $this->segments_ids = $this->filterSegmentIds($segments_ids);
    $this->batch_size = $batch_size;
    $this->offset = 0;
    $this->finished = false;
  }

  protected function filterSegmentIds($ids) {
    $ids = array_map(function($data) {
      return $data['id'];
    }, Segment::select('id')
      ->whereIn('id', $ids)
      ->where('type', Segment::TYPE_DEFAULT)
      ->findArray()
    );
    if($this->get_subscribers_without_segment) {
      $ids[] = 0;
    }
    return $ids;
  }

  /**
   * Resets the `offset` and `finished` properties;
   * to be able to start getting subscribers again.
   */
  public function reset() {
    $this->offset = 0;
    $this->finished = false;
  }

  /**
   * Gets the next batch of subscribers or `false` no more!
   */
  public function get() {
    if($this->finished) {
      return false;
    }
    // define returned columns
    $subscribers = Subscriber::selectMany(
      'first_name',
      'last_name',
      'email',
      'subscribed_ip',
      array(
        'global_status' => Subscriber::$_table . '.status'
      ),
      array(
        'list_status' => SubscriberSegment::$_table . '.status'
      )
    );

    // JOIN subscribers on segment and subscriber_segment tables
    $subscribers = $subscribers
      ->left_outer_join(
        SubscriberSegment::$_table,
        array(
          Subscriber::$_table . '.id',
          '=',
          SubscriberSegment::$_table . '.subscriber_id'
        )
      )
      ->left_outer_join(
        Segment::$_table,
        array(
          Segment::$_table . '.id',
          '=',
          SubscriberSegment::$_table . '.segment_id'
        )
      )
      ->filter('filterWithCustomFieldsForExport')
      ->groupBy(Subscriber::$_table . '.id')
      ->groupBy(Segment::$_table . '.id');

    if($this->get_subscribers_without_segment !== false) {
      // if there are subscribers who do not belong to any segment, use
      // a CASE function to group them under "Not In Segment"
      $subscribers = $subscribers
        ->selectExpr(
          'MAX(CASE WHEN ' . Segment::$_table . '.name IS NOT NULL ' .
          'THEN ' . Segment::$_table . '.name ' .
          'ELSE "' . __('Not In Segment', 'mailpoet') . '" END) as segment_name'
        )
        ->whereRaw(
          SubscriberSegment::$_table . '.segment_id IN (' .
          rtrim(str_repeat('?,', count($this->segments_ids)), ',') . ') ' .
          'OR ' . SubscriberSegment::$_table . '.segment_id IS NULL ',
          $this->segments_ids
        );
    } else {
      // if all subscribers belong to at least one segment, select the segment name
      $subscribers = $subscribers
        ->selectExpr('MAX(' . Segment::$_table . '.name) as segment_name')
        ->whereIn(SubscriberSegment::$_table . '.segment_id', $this->segments_ids);
    }
    $subscribers = $subscribers
      ->whereNull(Subscriber::$_table . '.deleted_at')
      ->offset($this->offset)
      ->limit($this->batch_size)
      ->findArray();

    $this->offset += $this->batch_size;

    if(count($subscribers) < $this->batch_size) {
      $this->finished = true;
    }

    return $subscribers;
  }
}