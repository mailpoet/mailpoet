<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

if(!defined('ABSPATH')) exit;

class Subscribers {
  static function get(array $queue) {
    $subscribers = unserialize($queue['subscribers']);
    if(empty($subscribers['processed'])) {
      $subscribers['processed'] = array();
    }
    if(empty($subscribers['failed'])) {
      $subscribers['failed'] = array();
    }
    return $subscribers;
  }

  static function updateToProcessList(
    array $existing_subscribers_ids,
    array $subscribers_to_process_ids,
    array $queue_subscribers_to_process_ids
  ) {
    // compare existing subscriber to the ones that queued for processing
    $subscibers_to_exclude = array_diff(
      $subscribers_to_process_ids,
      $existing_subscribers_ids
    );
    // remove nonexistent subscribers from the processing list
    return array_diff(
      $queue_subscribers_to_process_ids,
      $subscibers_to_exclude
    );
  }

  static function updateFailedList(array $subscribers, array $failed_subscribers) {
    return array_merge(
      $subscribers,
      $failed_subscribers
    );
  }
}