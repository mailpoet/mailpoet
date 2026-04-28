<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="statistics_unsubscribes")
 */
class StatisticsUnsubscribeEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use SafeToOneAssociationLoadTrait;

  const SOURCE_NEWSLETTER = 'newsletter';
  const SOURCE_MANAGE = 'manage';
  const SOURCE_ADMINISTRATOR = 'admin';
  const SOURCE_ORDER_CHECKOUT = 'order_checkout';
  const SOURCE_AUTOMATION = 'automation';
  const SOURCE_MP_API = 'mp_api';

  const METHOD_LINK = 'link';
  const METHOD_ONE_CLICK = 'one_click';
  const METHOD_UNKNOWN = 'unknown';

  const REASON_TOO_MANY_EMAILS = 'too_many_emails';
  const REASON_NOT_RELEVANT = 'not_relevant';
  const REASON_DO_NOT_REMEMBER_SIGNING_UP = 'do_not_remember_signing_up';
  const REASON_NO_LONGER_INTERESTED = 'no_longer_interested';
  const REASON_TOO_PROMOTIONAL = 'too_promotional';
  const REASON_OTHER = 'other';

  const REASONS = [
    self::REASON_TOO_MANY_EMAILS,
    self::REASON_NOT_RELEVANT,
    self::REASON_DO_NOT_REMEMBER_SIGNING_UP,
    self::REASON_NO_LONGER_INTERESTED,
    self::REASON_TOO_PROMOTIONAL,
    self::REASON_OTHER,
  ];

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @ORM\JoinColumn(name="newsletter_id", referencedColumnName="id")
   * @var NewsletterEntity|null
   */
  private $newsletter;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SendingQueueEntity")
   * @ORM\JoinColumn(name="queue_id", referencedColumnName="id")
   * @var SendingQueueEntity|null
   */
  private $queue;

  /**
   * @ORM\ManyToOne(targetEntity="MailPoet\Entities\SubscriberEntity")
   * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id")
   * @var SubscriberEntity|null
   */
  private $subscriber;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $source = 'unknown';

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $meta;

  /**
   * @ORM\Column(type="string", nullable=false)
   * @var string
   */
  private $method = self::METHOD_UNKNOWN;

  /**
   * @ORM\Column(type="string", length=80, nullable=true)
   * @var string|null
   */
  private $reason;

  /**
   * @ORM\Column(type="text", name="reason_text", nullable=true)
   * @var string|null
   */
  private $reasonText;

  /**
   * @ORM\Column(type="datetimetz", name="reason_submitted_at", nullable=true)
   * @var \DateTimeInterface|null
   */
  private $reasonSubmittedAt;

  public function __construct(
    ?NewsletterEntity $newsletter,
    ?SendingQueueEntity $queue,
    SubscriberEntity $subscriber
  ) {
    $this->newsletter = $newsletter;
    $this->queue = $queue;
    $this->subscriber = $subscriber;
  }

  /**
   * @return NewsletterEntity|null
   */
  public function getNewsletter() {
    $this->safelyLoadToOneAssociation('newsletter');
    return $this->newsletter;
  }

  /**
   * @return SendingQueueEntity|null
   */
  public function getQueue() {
    $this->safelyLoadToOneAssociation('queue');
    return $this->queue;
  }

  /**
   * @return string
   */
  public function getSource(): string {
    return $this->source;
  }

  /**
   * @param string $source
   */
  public function setSource(string $source) {
    $this->source = $source;
  }

  /**
   * @param string $meta
   */
  public function setMeta(string $meta) {
    $this->meta = $meta;
  }

  /**
   * @return string|null
   */
  public function getMeta() {
    return $this->meta;
  }

  public function setMethod(string $method) {
    $this->method = $method;
  }

  public function getMethod(): string {
    return $this->method;
  }

  public function setReason(?string $reason): void {
    $this->reason = $reason;
  }

  public function getReason(): ?string {
    return $this->reason;
  }

  public function setReasonText(?string $reasonText): void {
    $this->reasonText = $reasonText;
  }

  public function getReasonText(): ?string {
    return $this->reasonText;
  }

  public function setReasonSubmittedAt(?\DateTimeInterface $reasonSubmittedAt): void {
    $this->reasonSubmittedAt = $reasonSubmittedAt;
  }

  public function getReasonSubmittedAt(): ?\DateTimeInterface {
    return $this->reasonSubmittedAt;
  }

  public function setReasonData(string $reason, ?string $reasonText): void {
    $this->reason = $reason;
    $this->reasonText = $reasonText;
    $this->reasonSubmittedAt = Carbon::now()->millisecond(0);
  }
}
