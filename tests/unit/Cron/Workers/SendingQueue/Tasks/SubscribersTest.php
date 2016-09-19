<?php
use MailPoet\Cron\Workers\SendingQueue\Tasks\Subscribers;

if(!defined('ABSPATH')) exit;

class SubscribersTaskTest extends MailPoetTest {
  function testItCanSplitSubscribersInBatches() {
    $subscribers = range(1, 200);
    $split_subscribers = Subscribers::splitSubscribersIntoBatches($subscribers);
    expect(count($split_subscribers))->equals(200 / Subscribers::BATCH_SIZE);
    expect(count($split_subscribers[0]))->equals(Subscribers::BATCH_SIZE);
  }

  function testItCanRemoveNonexistentSubscribersFromListOfSubscribersToProcess() {
    $queue_subscribers = array(
      'to_process' => range(1, 5)
    );
    $subscribers_found = array(
      1,
      2,
      5
    );
    $subscribers_to_process = array(
      3,
      4
    );
    $updated_queue_subscribers = Subscribers::updateToProcessList(
      $subscribers_found,
      $subscribers_to_process,
      $queue_subscribers
    );
    expect($updated_queue_subscribers)->equals(
      array(
        'to_process' => array(
          1,
          2,
          5
        )
      )
    );
  }

  function testItCanUpdateListOfFailedSubscribers() {
    $queue_subscribers = array(
      'to_process' => range(1, 5),
      'failed' => array()
    );
    $failed_subscribers = array(
      1
    );
    $updated_queue_subscribers = Subscribers::updateFailedList(
      $failed_subscribers,
      $queue_subscribers
    );
    expect($updated_queue_subscribers)->equals(
      array(
        'to_process' => array(
          2,
          3,
          4,
          5
        ),
        'failed' => array(
          1
        )
      )
    );
  }

  function testItCanUpdateListOfProcessedSubscribers() {
    $queue_subscribers = array(
      'to_process' => range(1, 5),
      'processed' => array()
    );
    $processed_subscribers = array(
      1
    );
    $updated_queue_subscribers = Subscribers::updateProcessedList(
      $processed_subscribers,
      $queue_subscribers
    );
    expect($updated_queue_subscribers)->equals(
      array(
        'to_process' => array(
          2,
          3,
          4,
          5
        ),
        'processed' => array(
          1
        )
      )
    );
  }
}