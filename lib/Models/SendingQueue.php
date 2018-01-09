<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Emoji;

if(!defined('ABSPATH')) exit;

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUES_TABLE;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  function __construct() {
    parent::__construct();

    $this->addValidations('newsletter_rendered_body', array(
      'validRenderedNewsletterBody' => __('Rendered newsletter body is invalid!', 'mailpoet')
    ));
  }

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
    $this->newsletter_rendered_body = $this->getNewsletterRenderedBody();
    if(!is_serialized($this->subscribers) && !is_null($this->subscribers)) {
      $this->set('subscribers', serialize($this->subscribers));
    }
    if(!Helpers::isJson($this->newsletter_rendered_body) && !is_null($this->newsletter_rendered_body)) {
      $this->set(
        'newsletter_rendered_body',
        json_encode($this->encodeEmojisInBody($this->newsletter_rendered_body))
      );
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
    $rendered_newsletter = $this->decodeRenderedNewsletterBodyObject($this->newsletter_rendered_body);
    return ($type && !empty($rendered_newsletter[$type])) ?
      $rendered_newsletter[$type] :
      $rendered_newsletter;
  }

  function encodeEmojisInBody($newsletter_rendered_body) {
    if(is_array($newsletter_rendered_body)) {
      foreach($newsletter_rendered_body as $key => $value) {
        $newsletter_rendered_body[$key] = Emoji::encodeForUTF8Column(
          self::$_table,
          'newsletter_rendered_body',
          $value
        );
      }
    }
    return $newsletter_rendered_body;
  }

  function decodeEmojisInBody($newsletter_rendered_body) {
    if(is_array($newsletter_rendered_body)) {
      foreach($newsletter_rendered_body as $key => $value) {
        $newsletter_rendered_body[$key] = Emoji::decodeEntities($value);
      }
    }
    return $newsletter_rendered_body;
  }

  function isSubscriberProcessed($subscriber_id) {
    $subscribers = $this->getSubscribers();
    return in_array($subscriber_id, $subscribers['processed']);
  }

  function asArray() {
    $model = parent::asArray();
    $model['subscribers'] = $this->getSubscribers();
    $model['newsletter_rendered_body'] = $this->getNewsletterRenderedBody();
    return $model;
  }

  function removeSubscribers($subscribers_to_remove) {
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
    return $this->updateCount()->getErrors() === false;
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

  private function decodeRenderedNewsletterBodyObject($rendered_body) {
    if(is_serialized($rendered_body)) {
      return $this->decodeEmojisInBody(unserialize($rendered_body));
    }
    if(Helpers::isJson($rendered_body)) {
      return $this->decodeEmojisInBody(json_decode($rendered_body, true));
    }
    return $rendered_body;
  }
}