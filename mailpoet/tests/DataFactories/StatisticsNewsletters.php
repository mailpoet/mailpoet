<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Assert;

class StatisticsNewsletters {
  /** @var array */
  protected $data = [];

  /** @var NewsletterEntity */
  private $newsletter;

  /** @var SubscriberEntity */
  private $subscriber;

  public function __construct(
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber
  ) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
  }

  /**
   * @param \DateTimeInterface $date
   *
   * @return $this
   */
  public function withSentAt(\DateTimeInterface $date) {
    $this->data['sentAt'] = $date;
    return $this;
  }

  /** @return $this */
  public function withTrackingAllowed(bool $trackingAllowed) {
    $this->data['trackingAllowed'] = $trackingAllowed;
    return $this;
  }

  /**
   * Attach the row to a specific queue rather than the newsletter's latest one.
   *
   * @return $this
   */
  public function withQueue(SendingQueueEntity $queue) {
    $this->data['queue'] = $queue;
    return $this;
  }

  public function create(): StatisticsNewsletterEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $queue = $this->data['queue'] ?? $this->newsletter->getLatestQueue();
    Assert::assertInstanceOf(SendingQueueEntity::class, $queue);
    $entity = new StatisticsNewsletterEntity(
      $this->newsletter,
      $queue,
      $this->subscriber,
      null,
      $this->data['trackingAllowed'] ?? true
    );
    if (isset($this->data['sentAt'])) {
      $entity->setSentAt($this->data['sentAt']);
    }
    $entityManager->persist($entity);
    $entityManager->flush();
    return $entity;
  }
}
