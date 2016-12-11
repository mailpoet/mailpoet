<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUES_TABLE;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  function newsletter() {
    return $this->has_one(__NAMESPACE__ . '\Newsletter', 'id', 'newsletter_id');
  }

  function pause() {
    if($this->count_processed === $this->count_total) {
      return false;
    } else {
      $this->set('status', self::STATUS_PAUSED);
      $this->save();
      return ($this->getErrors() === false && $this->id() > 0);
    }
  }

  function resume() {
    if($this->count_processed === $this->count_total) {
      return $this->complete();
    } else {
      $this->setExpr('status', 'NULL');
      $this->save();
      return ($this->getErrors() === false && $this->id() > 0);
    }
  }

  function complete() {
    $this->set('status', self::STATUS_COMPLETED);
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  function save() {
    if(!is_serialized($this->subscribers)) {
      $this->set('subscribers', serialize($this->subscribers));
    }
    if(!is_serialized($this->newsletter_rendered_body)) {
      $this->set('newsletter_rendered_body', serialize($this->newsletter_rendered_body));
    }
    // set the default priority to medium
    if(!$this->priority) {
      $this->priority = self::PRIORITY_MEDIUM;
    }
    parent::save();
    $this->subscribers = $this->getSubscribers();
    $this->newsletter_rendered_body = $this->getNewsletterRenderedBody();
    return $this;
  }

  function getSubscribers() {
    if(!is_serialized($this->subscribers)) {
      return $this->subscribers;
    }
    $subscribers = unserialize($this->subscribers);
    if(empty($subscribers['processed'])) {
      $subscribers['processed'] = array();
    }
    return $subscribers;
  }

  function getNewsletterRenderedBody($type = false) {
    $rendered_newsletter = (!is_serialized($this->newsletter_rendered_body)) ?
      $this->newsletter_rendered_body :
      unserialize($this->newsletter_rendered_body);
    return ($type && !empty($rendered_newsletter[$type])) ?
      $rendered_newsletter[$type] :
      $rendered_newsletter;
  }

  function isSubscriberProcessed($subscriber_id) {
    $subscribers = $this->getSubscribers();
    return in_array($subscriber_id, $subscribers['processed']);
  }

  function asArray() {
    $model = parent::asArray();
    $model['subscribers'] = (is_serialized($this->subscribers))
      ? unserialize($this->subscribers)
      : $this->subscribers;
    return $model;
  }

  function removeNonexistentSubscribers($subscribers_to_remove) {
    $subscribers = $this->getSubscribers();
    $subscribers['to_process'] = array_values(
      array_diff(
        $subscribers['to_process'],
        $subscribers_to_remove
      )
    );
    $this->subscribers = $subscribers;
    $this->updateCount();
  }

  function updateProcessedSubscribers($processed_subscribers) {
    $subscribers = $this->getSubscribers();
    $subscribers['processed'] = array_merge(
      $subscribers['processed'],
      $processed_subscribers
    );
    $subscribers['to_process'] = array_values(
      array_diff(
        $subscribers['to_process'],
        $processed_subscribers
      )
    );
    $this->subscribers = $subscribers;
    $this->updateCount();
  }

  function updateCount() {
    $this->subscribers = $this->getSubscribers();
    $this->count_processed = count($this->subscribers['processed']);
    $this->count_to_process = count($this->subscribers['to_process']);
    $this->count_total = $this->count_processed + $this->count_to_process;
    if(!$this->count_to_process) {
      $this->processed_at = current_time('mysql');
      $this->status = self::STATUS_COMPLETED;
    }
    return $this->save();
  }
}