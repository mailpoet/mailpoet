<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;

/**
 * @Entity()
 * @Table(name="sending_queues")
 */
class SendingQueueEntity {
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  /**
   * @Column(type="json_or_serialized")
   * @var array|null
   */
  private $newsletter_rendered_body;

  /**
   * @Column(type="string")
   * @var string|null
   */
  private $newsletter_rendered_subject;

  /**
   * @Column(type="text")
   * @var string|null
   */
  private $subscribers;

  /**
   * @Column(type="integer")
   * @var int
   */
  private $count_total = 0;

  /**
   * @Column(type="integer")
   * @var int
   */
  private $count_processed = 0;

  /**
   * @Column(type="integer")
   * @var int
   */
  private $count_to_process = 0;

  /**
   * @Column(type="json")
   * @var array|null
   */
  private $meta;

  /**
   * @OneToOne(targetEntity="MailPoet\Entities\ScheduledTaskEntity")
   * @var ScheduledTaskEntity
   */
  private $task;

  /**
   * @OneToOne(targetEntity="MailPoet\Entities\NewsletterEntity", inversedBy="sending_queue")
   * @var NewsletterEntity
   */
  private $newsletter;

  /**
   * @return array|null
   */
  function getNewsletterRenderedBody() {
    return $this->newsletter_rendered_body;
  }

  /**
   * @param array|null $newsletter_rendered_body
   */
  function setNewsletterRenderedBody($newsletter_rendered_body) {
    $this->newsletter_rendered_body = $newsletter_rendered_body;
  }

  /**
   * @return string|null
   */
  function getNewsletterRenderedSubject() {
    return $this->newsletter_rendered_subject;
  }

  /**
   * @param string|null $newsletter_rendered_subject
   */
  function setNewsletterRenderedSubject($newsletter_rendered_subject) {
    $this->newsletter_rendered_subject = $newsletter_rendered_subject;
  }

  /**
   * @return string|null
   */
  function getSubscribers() {
    return $this->subscribers;
  }

  /**
   * @param string|null $subscribers
   */
  function setSubscribers($subscribers) {
    $this->subscribers = $subscribers;
  }

  /**
   * @return int
   */
  function getCountTotal() {
    return $this->count_total;
  }

  /**
   * @param int $count_total
   */
  function setCountTotal($count_total) {
    $this->count_total = $count_total;
  }

  /**
   * @return int
   */
  function getCountProcessed() {
    return $this->count_processed;
  }

  /**
   * @param int $count_processed
   */
  function setCountProcessed($count_processed) {
    $this->count_processed = $count_processed;
  }

  /**
   * @return int
   */
  function getCountToProcess() {
    return $this->count_to_process;
  }

  /**
   * @param int $count_to_process
   */
  function setCountToProcess($count_to_process) {
    $this->count_to_process = $count_to_process;
  }

  /**
   * @return array|null
   */
  function getMeta() {
    return $this->meta;
  }

  /**
   * @param array|null $meta
   */
  function setMeta($meta) {
    $this->meta = $meta;
  }

  /**
   * @return ScheduledTaskEntity
   */
  function getTask() {
    return $this->task;
  }

  function setTask(ScheduledTaskEntity $task) {
    $this->task = $task;
  }

  /**
   * @return NewsletterEntity
   */
  function getNewsletter() {
    return $this->newsletter;
  }

  function setNewsletter(NewsletterEntity $newsletter) {
    $this->newsletter = $newsletter;
  }
}
