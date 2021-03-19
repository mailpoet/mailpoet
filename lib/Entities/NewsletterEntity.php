<?php

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoet\Util\Helpers;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Collection;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
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
  use SafeToOneAssociationLoadTrait;

  /**
   * @ORM\Column(type="string", nullable=true)
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
  private $senderAddress = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $senderName = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $status = self::STATUS_DRAFT;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $replyToAddress = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $replyToName = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $preheader = '';

  /**
   * @ORM\Column(type="json", nullable=true)
   * @var array|null
   */
  private $body;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $sentAt;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $unsubscribeToken;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $gaCampaign = '';

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @var NewsletterEntity|null
   */
  private $parent;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\NewsletterEntity", mappedBy="parent")
   * @var NewsletterEntity[]|ArrayCollection
   */
  private $children;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\NewsletterSegmentEntity", mappedBy="newsletter", orphanRemoval=true)
   * @var NewsletterSegmentEntity[]|ArrayCollection
   */
  private $newsletterSegments;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\NewsletterOptionEntity", mappedBy="newsletter", orphanRemoval=true)
   * @var NewsletterOptionEntity[]|ArrayCollection
   */
  private $options;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\SendingQueueEntity", mappedBy="newsletter")
   * @var SendingQueueEntity[]|ArrayCollection
   */
  private $queues;

  public function __construct() {
    $this->children = new ArrayCollection();
    $this->newsletterSegments = new ArrayCollection();
    $this->options = new ArrayCollection();
    $this->queues = new ArrayCollection();
  }

  /**
   * @deprecated This is here only for backward compatibility with custom shortcodes https://kb.mailpoet.com/article/160-create-a-custom-shortcode
   * This can be removed after 2021-08-01
   */
  public function __get($key) {
    $getterName = 'get' . Helpers::underscoreToCamelCase($key, $capitaliseFirstChar = true);
    $callable = [$this, $getterName];
    if (is_callable($callable)) {
      return call_user_func($callable);
    }
  }

  public function __clone() {
    // reset ID
    $this->id = null;
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
    return $this->senderAddress;
  }

  /**
   * @param string $senderAddress
   */
  public function setSenderAddress($senderAddress) {
    $this->senderAddress = $senderAddress;
  }

  /**
   * @return string
   */
  public function getSenderName() {
    return $this->senderName;
  }

  /**
   * @param string $senderName
   */
  public function setSenderName($senderName) {
    $this->senderName = $senderName;
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

    // activate/deactivate unfinished tasks
    $newTaskStatus = null;
    $typesWithActivation = [self::TYPE_NOTIFICATION, self::TYPE_WELCOME, self::TYPE_AUTOMATIC];

    if (($status === self::STATUS_DRAFT) && in_array($this->type, $typesWithActivation)) {
      $newTaskStatus = ScheduledTaskEntity::STATUS_PAUSED;
    }
    if (($status === self::STATUS_ACTIVE) && in_array($this->type, $typesWithActivation)) {
      $newTaskStatus = ScheduledTaskEntity::STATUS_SCHEDULED;
    }

    if (!$newTaskStatus) return;

    $queues = $this->getUnfinishedQueues();

    foreach ($queues as $queue) {
      /** @var SendingQueueEntity $queue */
      $task = $queue->getTask();
      if ($task === null) continue;

      $scheduled = new Carbon($task->getScheduledAt());
      if ($scheduled < (new Carbon())->subDays(30)) continue;

      if (($status === self::STATUS_DRAFT) && ($task->getStatus() !== ScheduledTaskEntity::STATUS_SCHEDULED)) continue;
      if (($status === self::STATUS_ACTIVE) && ($task->getStatus() !== ScheduledTaskEntity::STATUS_PAUSED)) continue;

      $task->setStatus($newTaskStatus);
    }
  }

  /**
   * @return string
   */
  public function getReplyToAddress() {
    return $this->replyToAddress;
  }

  /**
   * @param string $replyToAddress
   */
  public function setReplyToAddress($replyToAddress) {
    $this->replyToAddress = $replyToAddress;
  }

  /**
   * @return string
   */
  public function getReplyToName() {
    return $this->replyToName;
  }

  /**
   * @param string $replyToName
   */
  public function setReplyToName($replyToName) {
    $this->replyToName = $replyToName;
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
    return $this->sentAt;
  }

  /**
   * @param DateTimeInterface|null $sentAt
   */
  public function setSentAt($sentAt) {
    $this->sentAt = $sentAt;
  }

  /**
   * @return string|null
   */
  public function getUnsubscribeToken() {
    return $this->unsubscribeToken;
  }

  /**
   * @return string
   */
  public function getGaCampaign() {
    return $this->gaCampaign;
  }

  /**
   * @param string $gaCampaign
   */
  public function setGaCampaign($gaCampaign) {
    $this->gaCampaign = $gaCampaign;
  }

  /**
   * @param string|null $unsubscribeToken
   */
  public function setUnsubscribeToken($unsubscribeToken) {
    $this->unsubscribeToken = $unsubscribeToken;
  }

  /**
   * @return NewsletterEntity|null
   */
  public function getParent() {
    $this->safelyLoadToOneAssociation('parent');
    return $this->parent;
  }

  /**
   * @param NewsletterEntity|null $parent
   */
  public function setParent($parent) {
    $this->parent = $parent;
  }

  /**
   * @return NewsletterEntity[]|ArrayCollection
   */
  public function getChildren() {
    return $this->children;
  }

  /**
   * @return NewsletterSegmentEntity[]|ArrayCollection
   */
  public function getNewsletterSegments() {
    return $this->newsletterSegments;
  }

  /**
   * @return NewsletterOptionEntity[]|ArrayCollection
   */
  public function getOptions() {
    return $this->options;
  }

  public function getOption(string $name): ?NewsletterOptionEntity {
    $option = $this->options->filter(function (NewsletterOptionEntity $option) use ($name): bool {
      return ($field = $option->getOptionField()) ? $field->getName() === $name : false;
    })->first();
    return $option ?: null;
  }

  public function getOptionValue(string $name) {
    $option = $this->getOption($name);
    return $option ? $option->getValue() : null;
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

  private function getUnfinishedQueues(): Collection {
    $criteria = new Criteria();
    $expr = Criteria::expr();
    $criteria->where($expr->neq('countToProcess', 0));
    return $this->queues->matching($criteria);
  }
}
