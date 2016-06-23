<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

if(!defined('ABSPATH')) exit;

class Subscribers {
  static function get($subscribers) {
    $subscribers = unserialize($subscribers);
    if(empty($subscribers['processed'])) {
      $subscribers['processed'] = array();
    }
    if(empty($subscribers['failed'])) {
      $subscribers['failed'] = array();
    }
    return $subscribers;
  }

  static function updateToProcessList(
    array $found_subscribers_ids,
    array $subscribers_to_process_ids,
    array $queue_subscribers
  ) {
    // compare existing subscriber to the ones that queued for processing
    $subscibers_to_exclude = array_diff(
      $subscribers_to_process_ids,
      $found_subscribers_ids
    );
    // remove nonexistent subscribers from the processing list
    $queue_subscribers['to_process'] = array_diff(
      $subscibers_to_exclude,
      $queue_subscribers['to_process']
    );
    return $queue_subscribers;
  }

  static function updateFailedList(
    array $failed_subscribers, array $queue_subscribers
  ) {
    $queue_subscribers['failed'] = array_merge(
      $queue_subscribers['failed'],
      $failed_subscribers
    );
    $queue_subscribers['to_process'] = array_diff(
      $queue_subscribers['to_process'],
      $failed_subscribers
    );
    return $queue_subscribers;
  }

  static function updateProcessedList(
    array $processed_subscribers, array $queue_subscribers
  ) {
    $queue_subscribers['processed'] = array_merge(
      $queue_subscribers['processed'],
      $processed_subscribers
    );
    $queue_subscribers['to_process'] = array_diff(
      $queue_subscribers['to_process'],
      $processed_subscribers
    );
    return $queue_subscribers;
  }
}