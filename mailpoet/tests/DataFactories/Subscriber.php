<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use DateTimeInterface;

class Subscriber {

  /** @var array */
  private $data;

  /** @var SegmentEntity[] */
  private $segments;

  public function __construct() {
    $this->data = [
      'email' => bin2hex(random_bytes(7)) . '@example.com', // phpcs:ignore
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ];
    $this->segments = [];
  }

  /**
   * @param string $firstName
   * @return $this
   */
  public function withFirstName($firstName) {
    $this->data['first_name'] = $firstName;
    return $this;
  }

  /**
   * @param string $lastName
   * @return $this
   */
  public function withLastName($lastName) {
    $this->data['last_name'] = $lastName;
    return $this;
  }

  /**
   * @param string $email
   * @return $this
   */
  public function withEmail($email) {
    $this->data['email'] = $email;
    return $this;
  }

  /**
   * @param string $status
   * @return $this
   */
  public function withStatus($status) {
    $this->data['status'] = $status;
    return $this;
  }

  public function withSource(string $source): self {
    $this->data['source'] = $source;
    return $this;
  }

  /**
   * @param int $count
   * @return $this
   */
  public function withCountConfirmations($count) {
    $this->data['count_confirmations'] = $count;
    return $this;
  }

  /**
   * @param int $score
   * @return $this
   */
  public function withEngagementScore($score) {
    $this->data['engagement_score'] = $score;
    return $this;
  }

  /**
   * @param bool $isWooCustomer
   * @return $this
   */
  public function withIsWooCommerceUser($isWooCustomer = true) {
    $this->data['is_woocommerce_user'] = $isWooCustomer;
    return $this;
  }

  /**
   * @return $this
   */
  public function withWpUserId(int $wpUserId) {
    $this->data['wp_user_id'] = $wpUserId;
    return $this;
  }

  /**
   * @param SegmentEntity[] $segments
   * @return $this
   */
  public function withSegments(array $segments) {
    $this->segments = [];
    foreach ($segments as $segment) {
      $this->segments[$segment->getId()] = $segment;
    }
    return $this;
  }

  /**
   * @param DateTimeInterface $createdAt
   * @return $this
   */
  public function withCreatedAt(DateTimeInterface $createdAt) {
    $this->data['setCreatedAt'] = $createdAt;
    return $this;
  }

  /**
   * @throws \Exception
   */
  public function create(): SubscriberEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus($this->data['status']);
    $subscriber->setEmail($this->data['email']);
    if (isset($this->data['count_confirmations'])) $subscriber->setConfirmationsCount($this->data['count_confirmations']);
    if (isset($this->data['engagement_score'])) $subscriber->setEngagementScore($this->data['engagement_score']);
    if (isset($this->data['last_name'])) $subscriber->setLastName($this->data['last_name']);
    if (isset($this->data['first_name'])) $subscriber->setFirstName($this->data['first_name']);
    if (isset($this->data['is_woocommerce_user'])) $subscriber->setIsWoocommerceUser($this->data['is_woocommerce_user']);
    if (isset($this->data['wp_user_id'])) $subscriber->setWpUserId($this->data['wp_user_id']);
    if (isset($this->data['source'])) {
      $subscriber->setSource($this->data['source']);
    }
    $entityManager->persist($subscriber);

    foreach ($this->segments as $segment) {
      $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, 'subscribed');
      $subscriber->getSubscriberSegments()->add($subscriberSegment);
      $entityManager->persist($subscriberSegment);
    }

    $entityManager->flush();
    return $subscriber;
  }
}
