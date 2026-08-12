<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="statistics_newsletters")
 */
class StatisticsNewsletterEntity {
  use AutoincrementedIdTrait;
  use SafeToOneAssociationLoadTrait;

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
   * @ORM\Column(type="datetimetz", nullable=false)
   * @var \DateTimeInterface
   */
  private $sentAt;

  /**
   * Whether the subscriber's tracking consent let us measure this send, as it
   * stood at the moment we sent. A snapshot, deliberately: consent is a current
   * fact about a person while a rate describes a past send, so reading consent
   * live would move historical rates and could push one over 100%.
   *
   * Defaults to true, so rows written before per-subscriber opt-out existed —
   * and any caller that does not care — keep today's meaning.
   *
   * @ORM\Column(type="boolean")
   * @var bool
   */
  private $trackingAllowed = true;

  public function __construct(
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    SubscriberEntity $subscriber,
    ?\DateTimeInterface $sentAt = null,
    bool $trackingAllowed = true
  ) {
    $this->newsletter = $newsletter;
    $this->queue = $queue;
    $this->subscriber = $subscriber;
    $this->sentAt = $sentAt ?: new \DateTimeImmutable();
    $this->trackingAllowed = $trackingAllowed;
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
   * @return SubscriberEntity|null
   */
  public function getSubscriber() {
    $this->safelyLoadToOneAssociation('subscriber');
    return $this->subscriber;
  }

  /**
   * @return \DateTimeInterface|null
   */
  public function getSentAt() {
    return $this->sentAt;
  }

  /**
   * @param \DateTimeInterface $sentAt
   */
  public function setSentAt(\DateTimeInterface $sentAt) {
    $this->sentAt = $sentAt;
  }

  public function getTrackingAllowed(): bool {
    return $this->trackingAllowed;
  }

  public function setTrackingAllowed(bool $trackingAllowed): void {
    $this->trackingAllowed = $trackingAllowed;
  }
}
