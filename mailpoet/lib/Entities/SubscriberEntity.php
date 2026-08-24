<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoet\Doctrine\EntityTraits\ValidationGroupsTrait;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;
use MailPoetVendor\Doctrine\Common\Collections\Collection;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;
use MailPoetVendor\Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="subscribers")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({"\MailPoet\Doctrine\EventListeners\SubscriberListener"})
 */
class SubscriberEntity {
  // hook names
  public const HOOK_SUBSCRIBER_CREATED = 'mailpoet_subscriber_created';
  public const HOOK_SUBSCRIBER_DELETED = 'mailpoet_subscriber_deleted';
  public const HOOK_SUBSCRIBER_UPDATED = 'mailpoet_subscriber_updated';
  public const HOOK_SUBSCRIBER_STATUS_CHANGED = 'mailpoet_subscriber_status_changed';
  public const HOOK_SUBSCRIBER_TRACKING_CONSENT_CHANGED = 'mailpoet_subscriber_tracking_consent_changed';
  public const HOOK_MULTIPLE_SUBSCRIBERS_CREATED = 'mailpoet_multiple_subscribers_created';
  public const HOOK_MULTIPLE_SUBSCRIBERS_DELETED = 'mailpoet_multiple_subscribers_deleted';
  public const HOOK_MULTIPLE_SUBSCRIBERS_UPDATED = 'mailpoet_multiple_subscribers_updated';
  public const HOOK_SUBSCRIBERS_COUNT_CHANGED = 'mailpoet_subscribers_count_changed';

  // statuses
  const STATUS_BOUNCED = 'bounced';
  const STATUS_INACTIVE = 'inactive';
  const STATUS_SUBSCRIBED = 'subscribed';
  const STATUS_UNCONFIRMED = 'unconfirmed';
  const STATUS_UNSUBSCRIBED = 'unsubscribed';

  // tracking consent
  const TRACKING_CONSENT_UNKNOWN = 'unknown';
  const TRACKING_CONSENT_GRANTED = 'granted';
  const TRACKING_CONSENT_DENIED = 'denied';

  const TRACKING_CONSENT_METHOD_FOOTER_LINK = 'footer_link';
  const TRACKING_CONSENT_METHOD_MANAGE_PAGE = 'manage_page';
  const TRACKING_CONSENT_METHOD_FORM = 'form';
  const TRACKING_CONSENT_METHOD_ADMIN = 'admin';
  const TRACKING_CONSENT_METHOD_IMPORT = 'import';
  const TRACKING_CONSENT_METHOD_WOOCOMMERCE_CHECKOUT = 'woocommerce_checkout';
  const TRACKING_CONSENT_METHOD_REGISTRATION = 'registration';
  const TRACKING_CONSENT_METHOD_COMMENT = 'comment';

  public const OBSOLETE_LINK_TOKEN_LENGTH = 6;
  public const LINK_TOKEN_LENGTH = 32;
  public const TIME_ZONE_FIELD_NAME = 'mailpoet_subscriber_timezone';
  public const TIME_ZONE_SOURCE_FORM = 'form';
  public const TIME_ZONE_SOURCE_MANUAL = 'manual';
  public const TIME_ZONE_SOURCE_SITE_FALLBACK = 'site_fallback';
  public const TIME_ZONE_CONFIDENCE_BROWSER = 90;
  public const TIME_ZONE_CONFIDENCE_MANUAL = 100;

  /** @var array<string,bool>|null */
  private static $validTimeZones = null;

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;
  use ValidationGroupsTrait;

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
   * CNIL/Garante: three states are legally distinct. `unknown` means we never
   * asked — it is NOT consent, and under the opt-in regime it must not be
   * treated as consent. How `unknown` is handled is a site setting; see
   * TrackingConsentController.
   *
   * @ORM\Column(type="string", length=20)
   * @Assert\Choice({"unknown", "granted", "denied"})
   * @var string
   */
  private $trackingConsent = self::TRACKING_CONSENT_UNKNOWN;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $trackingConsentUpdatedAt;

  /**
   * @ORM\Column(type="string", length=40, nullable=true)
   * @var string|null
   */
  private $trackingConsentMethod;

  /**
   * The exact wording shown when the choice was made. Required for proof of
   * consent (CNIL §6: a record of each person's consent "as well as the
   * conditions under which that consent was obtained").
   *
   * @ORM\Column(type="text", nullable=true)
   * @var string|null
   */
  private $trackingConsentCopy;

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
   * @Assert\Email(groups={"Saving"})
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
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $timeZone;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $timeZoneSource;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @var int|null
   */
  private $timeZoneConfidence;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $timeZoneUpdatedAt;

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
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastConfirmationEmailSentAt;

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
   * Denormalized number of subscribed memberships in non-deleted segments.
   * Maintained by SegmentsCountRecalculator; used to quickly find subscribers
   * without a list (segments_count = 0).
   * @ORM\Column(type="integer", options={"unsigned":true})
   * @var int
   */
  private $segmentsCount = 0;

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
  private $lastSendingAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastOpenAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastClickAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastPurchaseAt;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $lastPageViewAt;

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

  /**
   * @ORM\OneToMany(targetEntity="MailPoet\Entities\ScheduledTaskSubscriberEntity", mappedBy="subscriber", orphanRemoval=true)
   * @var Collection<int, ScheduledTaskSubscriberEntity>
   */
  private $scheduledTaskSubscribers;

  public function __construct() {
    $this->subscriberSegments = new ArrayCollection();
    $this->subscriberCustomFields = new ArrayCollection();
    $this->subscriberTags = new ArrayCollection();
    $this->scheduledTaskSubscribers = new ArrayCollection();
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

  public function getTrackingConsent(): string {
    return $this->trackingConsent;
  }

  /**
   * Setting the state also stamps when, how, and against what wording it
   * changed (CNIL/Garante record-keeping).
   */
  public function setTrackingConsent(string $consent, ?string $method = null, ?string $copy = null): void {
    if ($this->trackingConsent === $consent) {
      return;
    }
    $this->trackingConsent = $consent;
    $this->trackingConsentUpdatedAt = new \DateTimeImmutable();
    $this->trackingConsentMethod = $method;
    $this->trackingConsentCopy = $copy;
  }

  public function getTrackingConsentUpdatedAt(): ?DateTimeInterface {
    return $this->trackingConsentUpdatedAt;
  }

  public function getTrackingConsentMethod(): ?string {
    return $this->trackingConsentMethod;
  }

  public function getTrackingConsentCopy(): ?string {
    return $this->trackingConsentCopy;
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

  public function getTimeZone(): ?string {
    return $this->timeZone;
  }

  public function setTimeZone(?string $timeZone): void {
    $this->timeZone = $timeZone;
  }

  public function getTimeZoneSource(): ?string {
    return $this->timeZoneSource;
  }

  public function setTimeZoneSource(?string $timeZoneSource): void {
    $this->timeZoneSource = $timeZoneSource;
  }

  public function getTimeZoneConfidence(): ?int {
    return $this->timeZoneConfidence;
  }

  public function setTimeZoneConfidence(?int $timeZoneConfidence): void {
    $this->timeZoneConfidence = $timeZoneConfidence;
  }

  public function getTimeZoneUpdatedAt(): ?DateTimeInterface {
    return $this->timeZoneUpdatedAt;
  }

  public function setTimeZoneUpdatedAt(?DateTimeInterface $timeZoneUpdatedAt): void {
    $this->timeZoneUpdatedAt = $timeZoneUpdatedAt;
  }

  /**
   * @param mixed $timeZone
   */
  public static function sanitizeTimeZone($timeZone): ?string {
    if (!is_string($timeZone)) {
      return null;
    }
    $timeZone = trim($timeZone);
    if ($timeZone === '' || strlen($timeZone) > 64) {
      return null;
    }
    return self::isValidTimeZone($timeZone) ? $timeZone : null;
  }

  /**
   * @param mixed $timeZone
   */
  public static function isValidTimeZone($timeZone): bool {
    if (!is_string($timeZone) || $timeZone === '') {
      return false;
    }
    if (self::$validTimeZones === null) {
      self::$validTimeZones = array_fill_keys(\DateTimeZone::listIdentifiers(), true);
    }
    return isset(self::$validTimeZones[$timeZone]);
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
   * @return DateTimeInterface|null
   */
  public function getLastConfirmationEmailSentAt() {
    return $this->lastConfirmationEmailSentAt;
  }

  /**
   * @param DateTimeInterface|null $lastConfirmationEmailSentAt
   */
  public function setLastConfirmationEmailSentAt($lastConfirmationEmailSentAt) {
    $this->lastConfirmationEmailSentAt = $lastConfirmationEmailSentAt;
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
        'wordpress_user_deleted',
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

  public function getSegmentsCount(): int {
    return $this->segmentsCount;
  }

  public function setSegmentsCount(int $segmentsCount): void {
    $this->segmentsCount = $segmentsCount;
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
  public function getSubscriberSegments(?string $status = null) {
    if (!is_null($status)) {
      $criteria = Criteria::create()
        ->where(Criteria::expr()->eq('status', $status));
      $subscriberSegments = $this->subscriberSegments->matching($criteria);
    } else {
      $subscriberSegments = $this->subscriberSegments;
    }

    return $subscriberSegments;
  }

  /** * @return Collection<int, SegmentEntity> */
  public function getSegments() {
    return $this->subscriberSegments->map(function (?SubscriberSegmentEntity $subscriberSegment = null) {
      if (!$subscriberSegment) return null;
      return $subscriberSegment->getSegment();
    })->filter(function (?SegmentEntity $segment = null) {
      return $segment !== null;
    });
  }

  /**
   * @return Collection<int, SubscriberCustomFieldEntity>
   */
  public function getSubscriberCustomFields() {
    return $this->subscriberCustomFields;
  }

  public function getSubscriberCustomField(CustomFieldEntity $customField): ?SubscriberCustomFieldEntity {
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq('customField', $customField))
      ->setMaxResults(1);
    return $this->getSubscriberCustomFields()->matching($criteria)->first() ?: null;
  }

  /**
   * @return Collection<int, SubscriberTagEntity>
   */
  public function getSubscriberTags() {
    return $this->subscriberTags;
  }

  public function getSubscriberTag(TagEntity $tag): ?SubscriberTagEntity {
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq('tag', $tag))
      ->setMaxResults(1);
    return $this->getSubscriberTags()->matching($criteria)->first() ?: null;
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

  /**
   * Sets the raw engagement timestamp without touching status. Prefer markEngaged() when
   * recording a real engagement event (open, click, purchase, page view) so inactive
   * subscribers are reactivated immediately instead of waiting for the maintenance cron.
   */
  public function setLastEngagementAt(DateTimeInterface $lastEngagementAt): void {
    $this->lastEngagementAt = $lastEngagementAt;
  }

  /**
   * Records engagement and immediately reactivates the subscriber if they were inactive,
   * so they don't wait for the InactiveSubscribersMaintenance cron to be reactivated.
   */
  public function markEngaged(DateTimeInterface $engagedAt): void {
    $this->setLastEngagementAt($engagedAt);
    if ($this->getStatus() === self::STATUS_INACTIVE) {
      $this->setStatus(self::STATUS_SUBSCRIBED);
    }
  }

  public function getLastSendingAt(): ?DateTimeInterface {
    return $this->lastSendingAt;
  }

  public function setLastSendingAt(?DateTimeInterface $dateTime): void {
    $this->lastSendingAt = $dateTime;
  }

  public function getLastOpenAt(): ?DateTimeInterface {
    return $this->lastOpenAt;
  }

  public function setLastOpenAt(?DateTimeInterface $dateTime): void {
    $this->lastOpenAt = $dateTime;
  }

  public function getLastClickAt(): ?DateTimeInterface {
    return $this->lastClickAt;
  }

  public function setLastClickAt(?DateTimeInterface $dateTime): void {
    $this->lastClickAt = $dateTime;
  }

  public function getLastPurchaseAt(): ?DateTimeInterface {
    return $this->lastPurchaseAt;
  }

  public function setLastPurchaseAt(?DateTimeInterface $dateTime): void {
    $this->lastPurchaseAt = $dateTime;
  }

  public function getLastPageViewAt(): ?DateTimeInterface {
    return $this->lastPageViewAt;
  }

  public function setLastPageViewAt(?DateTimeInterface $dateTime): void {
    $this->lastPageViewAt = $dateTime;
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
    $this->subscriberSegments->map(function (?SubscriberSegmentEntity $subscriberSegment = null) {
      if (!$subscriberSegment) return null;
      if ($subscriberSegment->getSegment() === null) {
        $this->subscriberSegments->removeElement($subscriberSegment);
      }
    });
  }
}
