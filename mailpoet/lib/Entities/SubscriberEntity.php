<?php

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Collection;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="subscribers")
 * @ORM\HasLifecycleCallbacks
 */
class SubscriberEntity {
  // statuses
  const STATUS_BOUNCED = 'bounced';
  const STATUS_INACTIVE = 'inactive';
  const STATUS_SUBSCRIBED = 'subscribed';
  const STATUS_UNCONFIRMED = 'unconfirmed';
  const STATUS_UNSUBSCRIBED = 'unsubscribed';

  public const OBSOLETE_LINK_TOKEN_LENGTH = 6;
  public const LINK_TOKEN_LENGTH = 32;

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  /**
   * @ORM\Column(type="bigint", nullable=true)
   * @var string|null
   */
  private $wpUserId;

  /**
   * @ORM\Column(type="boolean")
   * @var bool
   */
  private $isWoocommerceUser = false;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $firstName = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $lastName = '';

  /**
   * @ORM\Column(type="string")
   * @Assert\Email()
   * @Assert\NotBlank()
   * @var string
   */
  private $email;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $status = self::STATUS_UNCONFIRMED;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $subscribedIp;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $confirmedIp;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $confirmedAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastSubscribedAt;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @var string|null
   */
  private $unconfirmedData;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $source = 'unknown';

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $countConfirmations = 0;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $unsubscribeToken;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $linkToken;

  /**
   * @ORM\Column(type="float", nullable=true)
   * @var float|null
   */
  private $engagementScore;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $engagementScoreUpdatedAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastEngagementAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $woocommerceSyncedAt;

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $emailCount = 0;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\SubscriberSegmentEntity", mappedBy="subscriber", orphanRemoval=true)
   * @var Collection<int, SubscriberSegmentEntity>
   */
  private $subscriberSegments;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\SubscriberCustomFieldEntity", mappedBy="subscriber", orphanRemoval=true)
   * @var Collection<int, SubscriberCustomFieldEntity>
   */
  private $subscriberCustomFields;

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\SubscriberTagEntity", mappedBy="subscriber", orphanRemoval=true)
   * @var Collection<int, SubscriberTagEntity>
   */
  private $subscriberTags;

  public function __construct() {
    $this->subscriberSegments = new ArrayCollection();
    $this->subscriberCustomFields = new ArrayCollection();
    $this->subscriberTags = new ArrayCollection();
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

  /**
   * @return int|null
   */
  public function getWpUserId() {
    return $this->wpUserId ? (int)$this->wpUserId : null;
  }

  /**
   * @param int|null $wpUserId
   */
  public function setWpUserId($wpUserId) {
    $this->wpUserId = $wpUserId ? (string)$wpUserId : null;
  }

  public function isWPUser(): bool {
    return $this->getWpUserId() > 0;
  }

  /**
   * @return bool
   */
  public function getIsWoocommerceUser() {
    return $this->isWoocommerceUser;
  }

  /**
   * @param bool $isWoocommerceUser
   */
  public function setIsWoocommerceUser($isWoocommerceUser) {
    $this->isWoocommerceUser = $isWoocommerceUser;
  }

  /**
   * @return string
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * @param string $firstName
   */
  public function setFirstName($firstName) {
    $this->firstName = $firstName;
  }

  /**
   * @return string
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * @param string $lastName
   */
  public function setLastName($lastName) {
    $this->lastName = $lastName;
  }

  /**
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail($email) {
    $this->email = $email;
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
    if (
      !in_array($status, [
        self::STATUS_BOUNCED,
        self::STATUS_INACTIVE,
        self::STATUS_SUBSCRIBED,
        self::STATUS_UNCONFIRMED,
        self::STATUS_UNSUBSCRIBED,
      ])
    ) {
      throw new \InvalidArgumentException("Invalid status '{$status}' given to subscriber!");
    }
    $this->status = $status;
  }

  /**
   * @return string|null
   */
  public function getSubscribedIp() {
    return $this->subscribedIp;
  }

  /**
   * @param string $subscribedIp
   */
  public function setSubscribedIp($subscribedIp) {
    $this->subscribedIp = $subscribedIp;
  }

  /**
   * @return string|null
   */
  public function getConfirmedIp() {
    return $this->confirmedIp;
  }

  /**
   * @param string|null $confirmedIp
   */
  public function setConfirmedIp($confirmedIp) {
    $this->confirmedIp = $confirmedIp;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getConfirmedAt() {
    return $this->confirmedAt;
  }

  /**
   * @param DateTimeInterface|null $confirmedAt
   */
  public function setConfirmedAt($confirmedAt) {
    $this->confirmedAt = $confirmedAt;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getLastSubscribedAt() {
    return $this->lastSubscribedAt;
  }

  /**
   * @param DateTimeInterface|null $lastSubscribedAt
   */
  public function setLastSubscribedAt($lastSubscribedAt) {
    $this->lastSubscribedAt = $lastSubscribedAt;
  }

  /**
   * @return string|null
   */
  public function getUnconfirmedData() {
    return $this->unconfirmedData;
  }

  /**
   * @param string|null $unconfirmedData
   */
  public function setUnconfirmedData($unconfirmedData) {
    $this->unconfirmedData = $unconfirmedData;
  }

  /**
   * @return string
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * @param string $source
   */
  public function setSource($source) {
    if (
      !in_array($source, [
        'api',
        'form',
        'unknown',
        'imported',
        'administrator',
        'wordpress_user',
        'woocommerce_user',
        'woocommerce_checkout',
      ])
    ) {
      throw new \InvalidArgumentException("Invalid source '{$source}' given to subscriber!");
    }
    $this->source = $source;
  }

  /**
   * @return int
   */
  public function getConfirmationsCount() {
    return $this->countConfirmations;
  }

  /**
   * @param int $countConfirmations
   */
  public function setConfirmationsCount($countConfirmations) {
    $this->countConfirmations = $countConfirmations;
  }

  /**
   * @return string|null
   */
  public function getUnsubscribeToken() {
    return $this->unsubscribeToken;
  }

  /**
   * @param string|null $unsubscribeToken
   */
  public function setUnsubscribeToken($unsubscribeToken) {
    $this->unsubscribeToken = $unsubscribeToken;
  }

  /**
   * @return string|null
   */
  public function getLinkToken() {
    return $this->linkToken;
  }

  /**
   * @param string|null $linkToken
   */
  public function setLinkToken($linkToken) {
    $this->linkToken = $linkToken;
  }

  /**
   * @return Collection<int, SubscriberSegmentEntity>
   */
  public function getSubscriberSegments() {
    return $this->subscriberSegments;
  }

  public function getSegments() {
    return $this->subscriberSegments->map(function (SubscriberSegmentEntity $subscriberSegment) {
      return $subscriberSegment->getSegment();
    })->filter(function ($segment) {
      return $segment !== null;
    });
  }

  /**
   * @return Collection<int, SubscriberCustomFieldEntity>
   */
  public function getSubscriberCustomFields() {
    return $this->subscriberCustomFields;
  }

  /**
   * @return Collection<int, SubscriberTagEntity>
   */
  public function getSubscriberTags() {
    return $this->subscriberTags;
  }

  /**
   * @return float|null
   */
  public function getEngagementScore(): ?float {
    return $this->engagementScore;
  }

  /**
   * @param float|null $engagementScore
   */
  public function setEngagementScore(?float $engagementScore): void {
    $this->engagementScore = $engagementScore;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getEngagementScoreUpdatedAt(): ?DateTimeInterface {
    return $this->engagementScoreUpdatedAt;
  }

  /**
   * @param DateTimeInterface|null $engagementScoreUpdatedAt
   */
  public function setEngagementScoreUpdatedAt(?DateTimeInterface $engagementScoreUpdatedAt): void {
    $this->engagementScoreUpdatedAt = $engagementScoreUpdatedAt;
  }

  public function getLastEngagementAt(): ?DateTimeInterface {
    return $this->lastEngagementAt;
  }

  public function setLastEngagementAt(DateTimeInterface $lastEngagementAt): void {
    $this->lastEngagementAt = $lastEngagementAt;
  }

  public function setWoocommerceSyncedAt(?DateTimeInterface $woocommerceSyncedAt): void {
    $this->woocommerceSyncedAt = $woocommerceSyncedAt;
  }

  public function getWoocommerceSyncedAt(): ?DateTimeInterface {
    return $this->woocommerceSyncedAt;
  }

  public function getEmailCount(): int {
    return $this->emailCount;
  }

  public function setEmailCount(int $emailCount): void {
    $this->emailCount = $emailCount;
  }

  /** @ORM\PreFlush */
  public function cleanupSubscriberSegments(): void {
    // Delete old orphan SubscriberSegments to avoid errors on update
    $this->subscriberSegments->map(function (SubscriberSegmentEntity $subscriberSegment) {
      if ($subscriberSegment->getSegment() === null) {
        $this->subscriberSegments->removeElement($subscriberSegment);
      }
    });
  }
}
