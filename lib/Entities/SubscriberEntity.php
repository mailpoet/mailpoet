<?php

namespace MailPoet\Entities;

use DateTimeInterface;
use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\DeletedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="subscribers")
 */
class SubscriberEntity {
  // statuses
  const STATUS_BOUNCED = 'bounced';
  const STATUS_INACTIVE = 'inactive';
  const STATUS_SUBSCRIBED = 'subscribed';
  const STATUS_UNCONFIRMED = 'unconfirmed';
  const STATUS_UNSUBSCRIBED = 'unsubscribed';

  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;
  use DeletedAtTrait;

  /**
   * @ORM\Column(type="bigint")
   * @var int|null
   */
  private $wp_user_id;

  /**
   * @ORM\Column(type="boolean")
   * @var bool
   */
  private $is_woocommerce_user = false;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $first_name = '';

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $last_name = '';

  /**
   * @ORM\Column(type="string")
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
  private $subscribed_ip;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $confirmed_ip;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $confirmed_at;

  /**
   * @ORM\Column(type="datetimetz", nullable=true)
   * @var DateTimeInterface|null
   */
  private $last_subscribed_at;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @var string|null
   */
  private $unconfirmed_data;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $source = 'unknown';

  /**
   * @ORM\Column(type="integer")
   * @var int
   */
  private $count_confirmations = 0;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string|null
   */
  private $unsubscribe_token;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $link_token;

  /**
   * @return int|null
   */
  public function getWpUserId() {
    return $this->wpUserId;
  }

  /**
   * @param int|null $wp_user_id
   */
  public function setWpUserId($wpUserId) {
    $this->wpUserId = $wpUserId;
  }

  /**
   * @return bool
   */
  public function getIsWoocommerceUser() {
    return $this->isWoocommerceUser;
  }

  /**
   * @param bool $is_woocommerce_user
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
   * @param string $first_name
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
   * @param string $last_name
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
    if (!in_array($status, [
      self::STATUS_BOUNCED,
      self::STATUS_INACTIVE,
      self::STATUS_SUBSCRIBED,
      self::STATUS_UNCONFIRMED,
      self::STATUS_UNSUBSCRIBED,
    ])) {
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
   * @param string $subscribed_ip
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
   * @param string|null $confirmed_ip
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
   * @param DateTimeInterface|null $confirmed_at
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
   * @param DateTimeInterface|null $last_subscribed_at
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
   * @param string|null $unconfirmed_data
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
    if (!in_array($source, [
      'api',
      'form',
      'unknown',
      'imported',
      'administrator',
      'wordpress_user',
      'woocommerce_user',
      'woocommerce_checkout',
    ])) {
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
   * @param int $count_confirmations
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
   * @param string|null $unsubscribe_token
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
   * @param string|null $link_token
   */
  public function setLinkToken($linkToken) {
    $this->linkToken = $linkToken;
  }

}
