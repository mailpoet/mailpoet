<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUES_TABLE;
  private $_newsletter_rendered_body;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';

  function newsletter() {
    return $this->has_one(__NAMESPACE__.'\Newsletter', 'id', 'newsletter_id');
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
    if(empty($subscribers['failed'])) {
      $subscribers['failed'] = array();
    }
    return $subscribers;
  }

  function getNewsletterRenderedBody() {
    return (!is_serialized($this->newsletter_rendered_body)) ?
      $this->newsletter_rendered_body :
      unserialize($this->newsletter_rendered_body);
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
}
