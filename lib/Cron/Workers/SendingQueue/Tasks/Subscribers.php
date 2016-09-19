<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

if(!defined('ABSPATH')) exit;

class Subscribers {
  const BATCH_SIZE = 50;

  static function splitSubscribersIntoBatches(array $subscribers) {
    return array_chunk(
      $subscribers,
      self::BATCH_SIZE
    );
  }

  static function updateToProcessList(
    $found_subscribers_ids,
    $subscribers_to_process_ids,
    $queue_subscribers
  ) {
    // compare existing subscribers to the ones that are queued for processing
    $subscibers_to_exclude = array_diff(
      $subscribers_to_process_ids,
      $found_subscribers_ids
    );
    // remove nonexistent subscribers from the processing list
    $queue_subscribers['to_process'] = array_values(
      array_diff(
        $queue_subscribers['to_process'],
        $subscibers_to_exclude
      )
    );
    return $queue_subscribers;
  }

  static function updateFailedList($failed_subscribers, $queue_subscribers) {
    $queue_subscribers['failed'] = array_merge(
      $queue_subscribers['failed'],
      $failed_subscribers
    );
    $queue_subscribers['to_process'] = array_values(
      array_diff(
        $queue_subscribers['to_process'],
        $failed_subscribers
      )
    );
    return $queue_subscribers;
  }

  static function updateProcessedList($processed_subscribers, $queue_subscribers) {
    $queue_subscribers['processed'] = array_merge(
      $queue_subscribers['processed'],
      $processed_subscribers
    );
    $queue_subscribers['to_process'] = array_values(
      array_diff(
        $queue_subscribers['to_process'],
        $processed_subscribers
      )
    );
    return $queue_subscribers;
  }
}