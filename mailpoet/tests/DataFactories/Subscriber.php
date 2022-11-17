<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use DateTimeInterface;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Subscriber {

  /** @var array */
  private $data;

  /** @var SegmentEntity[] */
  private $segments;

  /** @var TagEntity[] */
  private $tags;

  public function __construct() {
    $this->data = [
      'email' => bin2hex(random_bytes(7)) . '@example.com', // phpcs:ignore
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ];
    $this->segments = [];
    $this->tags = [];
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
   * @param TagEntity[] $tags
   * @return $this
   */
  public function withTags(array $tags) {
    $this->tags = [];
    foreach ($tags as $tag) {
      $this->tags[$tag->getId()] = $tag;
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
   * @param DateTimeInterface $deletedAt
   * @return $this
   */
  public function withDeletedAt(DateTimeInterface $deletedAt) {
    $this->data['deletedAt'] = $deletedAt;
    return $this;
  }

  /**
   * @return $this
   */
  public function withSubscribedIp(string $subscribedIp) {
    $this->data['subscribedIp'] = $subscribedIp;
    return $this;
  }

  /**
   * @return $this
   */
  public function withConfirmedIp(string $confirmedIp) {
    $this->data['confirmedIp'] = $confirmedIp;
    return $this;
  }

  /**
   * @return $this
   */
  public function withUnconfirmedData(string $unconfirmedData) {
    $this->data['unconfirmedData'] = $unconfirmedData;
    return $this;
  }

  /**
   * @param string $linkToken
   *
   * @return $this
   */
  public function withLinkToken(string $linkToken) {
    $this->data['linkToken'] = $linkToken;
    return $this;
  }

  /**
   * @return $this
   */
  public function withUpdatedAt(DateTimeInterface $updatedAt) {
    $this->data['updatedAt'] = $updatedAt;
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
    if (isset($this->data['subscribedIp'])) $subscriber->setSubscribedIp($this->data['subscribedIp']);
    if (isset($this->data['confirmedIp'])) $subscriber->setConfirmedIp($this->data['confirmedIp']);
    if (isset($this->data['unconfirmedData'])) $subscriber->setUnconfirmedData($this->data['unconfirmedData']);
    if (isset($this->data['source'])) {
      $subscriber->setSource($this->data['source']);
    }
    if (isset($this->data['linkToken'])) {
      $subscriber->setLinkToken($this->data['linkToken']);
    }

    if (isset($this->data['deletedAt'])) {
      $subscriber->setDeletedAt($this->data['deletedAt']);
    }

    $entityManager->persist($subscriber);

    foreach ($this->segments as $segment) {
      $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, 'subscribed');
      $subscriber->getSubscriberSegments()->add($subscriberSegment);
      $entityManager->persist($subscriberSegment);
    }

    foreach ($this->tags as $tag) {
      $subscriberTag = new SubscriberTagEntity($tag, $subscriber);
      $subscriber->getSubscriberTags()->add($subscriberTag);
      $entityManager->persist($subscriberTag);
    }

    $entityManager->flush();

    // workaround for storing updatedAt because it's set in TimestampListener
    if (isset($this->data['updatedAt'])) {
      $subscribersTable = $entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
      $entityManager->getConnection()->executeQuery("
        UPDATE $subscribersTable
        SET updated_at = '{$this->data['updatedAt']->format('Y-m-d H:i:s')}'
        WHERE id = {$subscriber->getId()}
      ");
      $entityManager->refresh($subscriber);
    }

    return $subscriber;
  }
}
