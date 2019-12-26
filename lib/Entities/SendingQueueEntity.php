<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sending_queues")
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
   * @ORM\Column(type="json_or_serialized")
   * @Assert\NotBlank()
   * @var array|null
   */
  private $newsletter_rendered_body;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $newsletter_rendered_subject;

  /**
   * @ORM\Column(type="text")
   * @var string|null
   */
  private $subscribers;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $count_total = 0;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $count_processed = 0;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $count_to_process = 0;

  /**
   * @ORM\Column(type="json")
   * @var array|null
   */
  private $meta;

  /**
   * @ORM\OneToOne(targetEntity="MailPoet\Entities\ScheduledTaskEntity")
   * @var ScheduledTaskEntity
   */
  private $task;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity", inversedBy="queues")
   * @var NewsletterEntity
   */
  private $newsletter;

  /**
   * @return array|null
   */
  public function getNewsletterRenderedBody() {
    return $this->newsletter_rendered_body;
  }

  /**
   * @param array|null $newsletter_rendered_body
   */
  public function setNewsletterRenderedBody($newsletter_rendered_body) {
    $this->newsletter_rendered_body = $newsletter_rendered_body;
  }

  /**
   * @return string|null
   */
  public function getNewsletterRenderedSubject() {
    return $this->newsletter_rendered_subject;
  }

  /**
   * @param string|null $newsletter_rendered_subject
   */
  public function setNewsletterRenderedSubject($newsletter_rendered_subject) {
    $this->newsletter_rendered_subject = $newsletter_rendered_subject;
  }

  /**
   * @return string|null
   */
  public function getSubscribers() {
    return $this->subscribers;
  }

  /**
   * @param string|null $subscribers
   */
  public function setSubscribers($subscribers) {
    $this->subscribers = $subscribers;
  }

  /**
   * @return int
   */
  public function getCountTotal() {
    return $this->count_total;
  }

  /**
   * @param int $count_total
   */
  public function setCountTotal($count_total) {
    $this->count_total = $count_total;
  }

  /**
   * @return int
   */
  public function getCountProcessed() {
    return $this->count_processed;
  }

  /**
   * @param int $count_processed
   */
  public function setCountProcessed($count_processed) {
    $this->count_processed = $count_processed;
  }

  /**
   * @return int
   */
  public function getCountToProcess() {
    return $this->count_to_process;
  }

  /**
   * @param int $count_to_process
   */
  public function setCountToProcess($count_to_process) {
    $this->count_to_process = $count_to_process;
  }

  /**
   * @return array|null
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * @param array|null $meta
   */
  public function setMeta($meta) {
    $this->meta = $meta;
  }

  /**
   * @return ScheduledTaskEntity
   */
  public function getTask() {
    return $this->task;
  }

  public function setTask(ScheduledTaskEntity $task) {
    $this->task = $task;
  }

  /**
   * @return NewsletterEntity
   */
  public function getNewsletter() {
    return $this->newsletter;
  }

  public function setNewsletter(NewsletterEntity $newsletter) {
    $this->newsletter = $newsletter;
  }
}
