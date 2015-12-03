<?php
namespace MailPoet\Cron;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterStatistics;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Mailer;

if(!defined('ABSPATH')) exit;

class Worker {
  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
  }
  
  function process() {
    $queues =
      SendingQueue::orderByDesc('priority')
        ->whereNull('deleted_at')
        ->whereNull('status')
        ->findResultSet();
    // TODO: usee Mailer's method to get the mailer from DB
    $mailer = new Mailer();
    $mailer->mailer['method'] = 'MailPoet';
    $mailer->mailer['class'] = 'MailPoet\\Mailer\\MailPoet';
    $mailer->mailer['api_key'] = Setting::getValue('api_key', 'dhNSqj1XHkVltIliyQDvMiKzQShOA5rs0m_DdRUVZHU');
    foreach($queues as $queue) {
      if($this->checkExecutionTimer()) break;
      $newsletter = Newsletter::findOne($queue->newsletter_id);
      if(!$newsletter) {
        continue;
      };
      $newsletter = $newsletter->asArray();
      // TODO: render newsletter
      $newsletter = array(
        'subject' => $newsletter['subject'],
        'id' => $newsletter['id'],
        'body' => array(
          'html' => 'rendering not yet implemented',
          'text' => 'rendering not yet implemented'
        )
      );
      $subscribers = json_decode($queue->subscribers, true);
      $subscribersToProcess = $subscribers['to_process'];
      if(!isset($subscribers['failed'])) $subscribers['failed'] = array();
      if(!isset($subscribers['processed'])) $subscribers['processed'] = array();
      foreach(array_chunk($subscribersToProcess, 200) as $subscriberIds) {
        if($this->checkExecutionTimer()) break;
        $dbSubscribers = Subscriber::whereIn('id', $subscriberIds)
          ->findArray();
        foreach($dbSubscribers as $i => $dbSubscriber) {
          if($this->checkExecutionTimer()) break;
          // TODO: replace shortcodes in the newsletter
          $result = $mailer->send(
            $newsletter,
            sprintf(
              "%s %s <%s>",
              $dbSubscriber['first_name'],
              $dbSubscriber['last_name'],
              $dbSubscriber['email']
            )
          );
          $newsletterStatistics = NewsletterStatistics::create();
          $newsletterStatistics->subscriber_id = $dbSubscriber['id'];
          $newsletterStatistics->newsletter_id = $newsletter['id'];
          $newsletterStatistics->queue_id = $queue->id;
          $newsletterStatistics->save();
          if($result) {
            $subscribers['processed'][] = $dbSubscriber['id'];
          } else {
            $subscribers['failed'][] = $dbSubscriber['id'];
          }
          $subscribers['to_process'] = array_values(
            array_diff(
              $subscribers['to_process'],
              array_merge($subscribers['processed'], $subscribers['failed'])
            )
          );
          $queue->count_processed =
            count($subscribers['processed']) + count($subscribers['failed']);
          $queue->count_to_process = count($subscribers['to_process']);
          $queue->count_failed = count($subscribers['failed']);
          $queue->count_total =
            $queue->count_processed + $queue->count_to_process;
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

  function checkExecutionTimer() {
    $elapsedTime = microtime(true) - $this->timer;
    return ($elapsedTime >= 28) ? true : false;
  }
}