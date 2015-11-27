<?php
namespace MailPoet\Queue;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterStatistics;
use MailPoet\Models\Queue;

if(!defined('ABSPATH')) exit;

class Worker {
  function __construct($timer = false) {
    $this->timer = $timer;
    $this->timer = microtime(true);
  }
  
  function process() {
    $queues =
      Queue::orderByDesc('priority')
        ->whereNotIn('status', array(
          'paused',
          'completed'
        ))
        ->findResultSet();
    foreach ($queues as $queue) {
      $newsletter = Newsletter::findOne($queue->newsletter_id)
        ->asArray();
      $subscribers = json_decode($queue->subscribers, true);
      if(!isset($subscribers['failed'])) $subscribers['failed'] = array();
      if(!isset($subscribers['processed'])) $subscribers['processed'] = array();
      $subscribersToProcess = $subscribers['to_process'];
      foreach ($subscribersToProcess as $subscriber) {
        $elapsedTime = microtime(true) - $this->timer;
        if($elapsedTime >= 28) break;
        // TODO: hook up to mailer
        sleep(1);
        $newsletterStatistics = NewsletterStatistics::create();
        $newsletterStatistics->subscriber_id = $subscriber;
        $newsletterStatistics->newsletter_id = $newsletter['id'];
        $newsletterStatistics->queue_id = $queue->id;
        $newsletterStatistics->save();
        $subscribers['processed'][] = $subscriber;
        $subscribers['to_process'] = array_values(
          array_diff(
            $subscribers['to_process'],
            $subscribers['processed']
          )
        );
        $queue->count_processed = count($subscribers['processed']);
        $queue->count_to_process = count($subscribers['to_process']);
        $queue->count_failed = count($subscribers['failed']);
        $queue->count_total =
          $queue->count_processed + $queue->count_to_process + $queue->count_failed;
        if(!$queue->count_to_process) {
          $queue->processed_at = date('Y-m-d H:i:s');
          $queue->status = 'completed';
        }
        $queue->subscribers = json_encode($subscribers);
        $queue->save();
      }
    }
  }
}