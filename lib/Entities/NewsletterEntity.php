<?php

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
use MailPoetVendor\Doctrine\ORM\EntityNotFoundException;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="newsletters")
 */
class NewsletterEntity {
  // types
  const TYPE_AUTOMATIC = 'automatic';
  const TYPE_STANDARD = 'standard';
  const TYPE_WELCOME = 'welcome';
  const TYPE_NOTIFICATION = 'notification';
  const TYPE_NOTIFICATION_HISTORY = 'notification_history';
  const TYPE_WC_TRANSACTIONAL_EMAIL = 'wc_transactional';

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
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $hash;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $subject;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank()
   * @var string
   */
  private $type;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $sender_address = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $sender_name = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $status = self::STATUS_DRAFT;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $reply_to_address = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $reply_to_name = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $preheader = '';

  /**
   * @ORM\Column(type="json")
   * @var array|null
   */
  private $body;

  /**
   * @ORM\Column(type="datetimetz")
   * @var DateTimeInterface|null
   */
  private $sent_at;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $unsubscribe_token;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $ga_campaign = '';

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @var NewsletterEntity|null
   */
  private $parent;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\NewsletterSegmentEntity", mappedBy="newsletter")
   * @var NewsletterSegmentEntity[]|ArrayCollection
   */
  private $newsletter_segments;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\NewsletterOptionEntity", mappedBy="newsletter")
   * @var NewsletterOptionEntity[]|ArrayCollection
   */
  private $options;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\SendingQueueEntity", mappedBy="newsletter")
   * @var SendingQueueEntity[]|ArrayCollection
   */
  private $queues;

  public function __construct() {
    $this->newsletter_segments = new ArrayCollection();
    $this->options = new ArrayCollection();
    $this->queues = new ArrayCollection();
  }

  /**
   * @return string|null
   */
  public function getHash() {
    return $this->hash;
  }

  /**
   * @param string|null $hash
   */
  public function setHash($hash) {
    $this->hash = $hash;
  }

  /**
   * @return string
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * @param string $subject
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @param string $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * @return string
   */
  public function getSenderAddress() {
    return $this->sender_address;
  }

  /**
   * @param string $sender_address
   */
  public function setSenderAddress($sender_address) {
    $this->sender_address = $sender_address;
  }

  /**
   * @return string
   */
  public function getSenderName() {
    return $this->sender_name;
  }

  /**
   * @param string $sender_name
   */
  public function setSenderName($sender_name) {
    $this->sender_name = $sender_name;
  }

  /**
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param string $status
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return string
   */
  public function getReplyToAddress() {
    return $this->reply_to_address;
  }

  /**
   * @param string $reply_to_address
   */
  public function setReplyToAddress($reply_to_address) {
    $this->reply_to_address = $reply_to_address;
  }

  /**
   * @return string
   */
  public function getReplyToName() {
    return $this->reply_to_name;
  }

  /**
   * @param string $reply_to_name
   */
  public function setReplyToName($reply_to_name) {
    $this->reply_to_name = $reply_to_name;
  }

  /**
   * @return string
   */
  public function getPreheader() {
    return $this->preheader;
  }

  /**
   * @param string $preheader
   */
  public function setPreheader($preheader) {
    $this->preheader = $preheader;
  }

  /**
   * @return array|null
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * @param array|null $body
   */
  public function setBody($body) {
    $this->body = $body;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getSentAt() {
    return $this->sent_at;
  }

  /**
   * @param DateTimeInterface|null $sent_at
   */
  public function setSentAt($sent_at) {
    $this->sent_at = $sent_at;
  }

  /**
   * @return string|null
   */
  public function getUnsubscribeToken() {
    return $this->unsubscribe_token;
  }

  /**
   * @return string
   */
  public function getGaCampaign() {
    return $this->ga_campaign;
  }

  /**
   * @param string $ga_campaign
   */
  public function setGaCampaign($ga_campaign) {
    $this->ga_campaign = $ga_campaign;
  }


  /**
   * @param string|null $unsubscribe_token
   */
  public function setUnsubscribeToken($unsubscribe_token) {
    $this->unsubscribe_token = $unsubscribe_token;
  }

  /**
   * @return NewsletterEntity|null
   */
  public function getParent() {
    try {
      if ($this->parent && $this->parent->getId()) {
        return $this->parent;
      }
    } catch (EntityNotFoundException $enf) {
      $this->setParent(null);
    }
    return null;
  }

  /**
   * @param NewsletterEntity|null $parent
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * @return NewsletterSegmentEntity[]|ArrayCollection
   */
  public function getNewsletterSegments() {
    return $this->newsletter_segments;
  }

  /**
   * @return NewsletterOptionEntity[]|ArrayCollection
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * @return SendingQueueEntity[]|ArrayCollection
   */
  public function getQueues() {
    return $this->queues;
  }

  /**
   * @return SendingQueueEntity|null
   */
  public function getLatestQueue() {
    $criteria = new Criteria();
    $criteria->orderBy(['id' => Criteria::DESC]);
    $criteria->setMaxResults(1);
    return $this->queues->matching($criteria)->first() ?: null;
  }
}
