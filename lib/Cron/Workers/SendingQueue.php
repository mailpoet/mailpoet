<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterStatistics;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Renderer\Renderer;

if(!defined('ABSPATH')) exit;

class SendingQueue {
  function __construct($timer = false) {
    $this->timer = ($timer) ? $timer : microtime(true);
  }

  function process() {
    foreach($this->getQueues() as $queue) {
      $newsletter = Newsletter::findOne($queue->newsletter_id)
        ->asArray();
      if(!$newsletter) {
        continue;
      };
      $newsletter = $this->renderNewsletter($newsletter);
      $mailer = $this->configureMailerForNewsletter($newsletter);
      $subscribers = json_decode($queue->subscribers, true);
      $subscribersToProcess = $subscribers['to_process'];
      if(!isset($subscribers['processed'])) $subscribers['processed'] = array();
      if(!isset($subscribers['failed'])) $subscribers['failed'] = array();
      foreach(array_chunk($subscribersToProcess, 200) as $subscriberIds) {
        $dbSubscribers = Subscriber::whereIn('id', $subscriberIds)
          ->findArray();
        foreach($dbSubscribers as $dbSubscriber) {
          $this->checkExecutionTimer();
          $result = $this->sendNewsletter(
            $mailer,
            $this->processNewsletter($newsletter),
            $dbSubscriber);
          if($result) {
            $this->updateStatistics($newsletter['id'], $dbSubscriber['id'], $queue->id);
            $subscribers['processed'][] = $dbSubscriber['id'];
          } else $subscribers['failed'][] = $dbSubscriber['id'];
          $this->updateQueue($queue, $subscribers);
        }
      }
    }
  }

  function processNewsletter($newsletter) {
    // TODO: replace shortcodes, etc..
    return $newsletter;
  }

  function sendNewsletter($mailer, $newsletter, $subscriber) {
    return $mailer->mailerInstance->send(
      $newsletter,
      $mailer->transformSubscriber($subscriber)
    );
  }

  function updateStatistics($newsletterId, $subscriberId, $queueId) {
    $newsletterStatistics = NewsletterStatistics::create();
    $newsletterStatistics->subscriber_id = $newsletterId;
    $newsletterStatistics->newsletter_id = $subscriberId;
    $newsletterStatistics->queue_id = $queueId;
    $newsletterStatistics->save();
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
    } else $sender = false;
    if(!empty($newsletter['reply_to_address']) && !empty($newsletter['reply_to_name'])) {
      $replyTo = array(
        'name' => $newsletter['reply_to_name'],
        'address' => $newsletter['reply_to_address']
      );
    } else $replyTo = false;
    $mailer = new Mailer($method = false, $sender, $replyTo);
    return $mailer;
  }

  function checkExecutionTimer() {
    $elapsedTime = microtime(true) - $this->timer;
    if($elapsedTime >= 30) throw new \Exception('Maximum execution time reached.');
  }

  function getQueues() {
    return \MailPoet\Models\SendingQueue::orderByDesc('priority')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->findResultSet();
  }

  function renderNewsletter($newsletter) {
    $renderer = new Renderer(json_decode($newsletter['body'], true));
    $newsletter['body'] = $renderer->renderAll();
    return $newsletter;
  }
}