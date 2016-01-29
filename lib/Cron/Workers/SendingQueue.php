<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterStatistics;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  public $timer;

  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
  }

  function process() {
    // TODO: implement mailer sending frequency limits
    foreach($this->getQueues() as $queue) {
      $newsletter = Newsletter::findOne($queue->newsletter_id)
        ->asArray();
      if(!$newsletter) {
        continue;
      };
      $mailer = $this->configureMailerForNewsletter($newsletter);
      $subscribers = json_decode($queue->subscribers, true);
      $subscribers_to_process = $subscribers['to_process'];
      if(!isset($subscribers['processed'])) $subscribers['processed'] = array();
      if(!isset($subscribers['failed'])) $subscribers['failed'] = array();
      foreach(array_chunk($subscribers_to_process, 200) as $subscriber_ids) {
        $db_subscribers = Subscriber::whereIn('id', $subscriber_ids)
          ->findArray();
        foreach($db_subscribers as $db_subscriber) {
          $this->checkExecutionTimer();
          $result = $this->sendNewsletter(
            $mailer,
            $this->processNewsletter($newsletter, $db_subscriber),
            $db_subscriber);
          if($result) {
            $this->updateStatistics($newsletter['id'], $db_subscriber['id'], $queue->id);
            $subscribers['processed'][] = $db_subscriber['id'];
          } else {
            $subscribers['failed'][] = $db_subscriber['id'];
          }
          $this->updateQueue($queue, $subscribers);
        }
      }
    }
  }

  function processNewsletter($newsletter, $subscriber) {
    $rendered_newsletter = $this->renderNewsletter($newsletter);
    $shortcodes = new Shortcodes($rendered_newsletter['body']['html'], $newsletter, $subscriber);
    $processed_newsletter['body']['html'] = $shortcodes->replace();
    $shortcodes = new Shortcodes($rendered_newsletter['body']['text'], $newsletter, $subscriber);
    $processed_newsletter['body']['text'] = $shortcodes->replace();
    $processed_newsletter['subject'] = $rendered_newsletter['subject'];
    return $processed_newsletter;
  }

  function sendNewsletter($mailer, $newsletter, $subscriber) {
    return $mailer->mailer_instance->send(
      $newsletter,
      $mailer->transformSubscriber($subscriber)
    );
  }

  function updateStatistics($newsletter_id, $subscriber_id, $queue_id) {
    $newsletter_statistic = NewsletterStatistics::create();
    $newsletter_statistic->subscriber_id = $newsletter_id;
    $newsletter_statistic->newsletter_id = $subscriber_id;
    $newsletter_statistic->queue_id = $queue_id;
    $newsletter_statistic->save();
  }

  function updateQueue($queue, $subscribers) {
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

  function configureMailerForNewsletter($newsletter) {
    if(!empty($newsletter['sender_address']) && !empty($newsletter['sender_name'])) {
      $sender = array(
        'name' => $newsletter['sender_name'],
        'address' => $newsletter['sender_address']
      );
    } else {
      $sender = false;
    }
    if(!empty($newsletter['reply_to_address']) && !empty($newsletter['reply_to_name'])) {
      $reply_to = array(
        'name' => $newsletter['reply_to_name'],
        'address' => $newsletter['reply_to_address']
      );
    } else {
      $reply_to = false;
    }
    $mailer = new Mailer($method = false, $sender, $reply_to);
    return $mailer;
  }

  function checkExecutionTimer() {
    $elapsed_time = microtime(true) - $this->timer;
    if($elapsed_time >= 30) throw new \Exception('Maximum execution time reached.');
  }

  function getQueues() {
    return \MailPoet\Models\SendingQueue::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findResultSet();
  }

  function renderNewsletter($newsletter) {
    $renderer = new Renderer($newsletter);
    $newsletter['body'] = $renderer->render();
    return $newsletter;
  }
}