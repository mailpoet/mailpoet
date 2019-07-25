<?php

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Collection;
use MailPoetVendor\Doctrine\ORM\Mapping\Column;

/**
 * @Entity()
 * @Table(name="newsletters")
 */
class NewsletterEntity {
  // types
  const TYPE_AUTOMATIC = 'automatic';
  const TYPE_STANDARD = 'standard';
  const TYPE_WELCOME = 'welcome';
  const TYPE_NOTIFICATION = 'notification';
  const TYPE_NOTIFICATION_HISTORY = 'notification_history';

  // standard newsletters
  const STATUS_DRAFT = 'draft';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_SENDING = 'sending';
  const STATUS_SENT = 'sent';

  // automatic newsletters status
  const STATUS_ACTIVE = 'active';

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  /**
   * @Column(type="string")
   * @var string|null
   */
  private $hash;

  /**
   * @Column(type="string")
   * @var string
   */
  private $subject;

  /**
   * @Column(type="string")
   * @var string
   */
  private $type;

  /**
   * @Column(type="string")
   * @var string
   */
  private $sender_address;

  /**
   * @Column(type="string")
   * @var string
   */
  private $sender_name;

  /**
   * @Column(type="string")
   * @var string
   */
  private $status = self::STATUS_DRAFT;

  /**
   * @Column(type="string")
   * @var string
   */
  private $reply_to_address;

  /**
   * @Column(type="string")
   * @var string
   */
  private $reply_to_name;

  /**
   * @Column(type="string")
   * @var string
   */
  private $preheader;

  /**
   * @Column(type="json")
   * @var array|null
   */
  private $body;

  /**
   * @Column(type="datetimetz")
   * @var DateTimeInterface|null
   */
  private $sent_at;

  /**
   * @Column(type="string")
   * @var string|null
   */
  private $unsubscribe_token;

  /**
   * @ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @var NewsletterEntity|null
   */
  private $parent;

  /**
   * @OneToMany(targetEntity="MailPoet\Entities\NewsletterSegmentEntity", mappedBy="newsletter")
   * @var NewsletterSegmentEntity[]|Collection
   */
  private $newsletter_segments;

  /**
   * @OneToMany(targetEntity="MailPoet\Entities\NewsletterOptionEntity", mappedBy="newsletter")
   * @var NewsletterOptionEntity[]|Collection
   */
  private $options;

  /**
   * @OneToOne(targetEntity="MailPoet\Entities\SendingQueueEntity", mappedBy="newsletter")
   * @var SendingQueueEntity|null
   */
  private $queue;

  function __construct() {
    $this->newsletter_segments = new ArrayCollection();
    $this->options = new ArrayCollection();
  }

  /**
   * @return string|null
   */
  function getHash() {
    return $this->hash;
  }

  /**
   * @param string|null $hash
   */
  function setHash($hash) {
    $this->hash = $hash;
  }

  /**
   * @return string
   */
  function getSubject() {
    return $this->subject;
  }

  /**
   * @param string $subject
   */
  function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * @return string
   */
  function getType() {
    return $this->type;
  }

  /**
   * @param string $type
   */
  function setType($type) {
    $this->type = $type;
  }

  /**
   * @return string
   */
  function getSenderAddress() {
    return $this->sender_address;
  }

  /**
   * @param string $sender_address
   */
  function setSenderAddress($sender_address) {
    $this->sender_address = $sender_address;
  }

  /**
   * @return string
   */
  function getSenderName() {
    return $this->sender_name;
  }

  /**
   * @param string $sender_name
   */
  function setSenderName($sender_name) {
    $this->sender_name = $sender_name;
  }

  /**
   * @return string
   */
  function getStatus() {
    return $this->status;
  }

  /**
   * @param string $status
   */
  function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return string
   */
  function getReplyToAddress() {
    return $this->reply_to_address;
  }

  /**
   * @param string $reply_to_address
   */
  function setReplyToAddress($reply_to_address) {
    $this->reply_to_address = $reply_to_address;
  }

  /**
   * @return string
   */
  function getReplyToName() {
    return $this->reply_to_name;
  }

  /**
   * @param string $reply_to_name
   */
  function setReplyToName($reply_to_name) {
    $this->reply_to_name = $reply_to_name;
  }

  /**
   * @return string
   */
  function getPreheader() {
    return $this->preheader;
  }

  /**
   * @param string $preheader
   */
  function setPreheader($preheader) {
    $this->preheader = $preheader;
  }

  /**
   * @return array|null
   */
  function getBody() {
    return $this->body;
  }

  /**
   * @param array|null $body
   */
  function setBody($body) {
    $this->body = $body;
  }

  /**
   * @return DateTimeInterface|null
   */
  function getSentAt() {
    return $this->sent_at;
  }

  /**
   * @param DateTimeInterface|null $sent_at
   */
  function setSentAt($sent_at) {
    $this->sent_at = $sent_at;
  }

  /**
   * @return string|null
   */
  function getUnsubscribeToken() {
    return $this->unsubscribe_token;
  }

  /**
   * @param string|null $unsubscribe_token
   */
  function setUnsubscribeToken($unsubscribe_token) {
    $this->unsubscribe_token = $unsubscribe_token;
  }

  /**
   * @return NewsletterEntity|null
   */
  function getParent() {
    return $this->parent;
  }

  /**
   * @param NewsletterEntity|null $parent
   */
  function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * @return NewsletterSegmentEntity[]|Collection
   */
  function getNewsletterSegments() {
    return $this->newsletter_segments;
  }

  /**
   * @return NewsletterOptionEntity[]|Collection
   */
  function getOptions() {
    return $this->options;
  }

  /**
   * @return SendingQueueEntity|null
   */
  function getQueue() {
    return $this->queue;
  }

  /**
   * @param SendingQueueEntity|null $queue
   */
  function setQueue($queue) {
    $this->queue = $queue;
  }
}
