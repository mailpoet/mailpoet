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
   * @var bool|null
   */
  private $is_woocommerce_user;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $first_name;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $last_name;

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
   * @ORM\Column(type="string")
   * @var string
   */
  private $subscribed_ip;

  /**
   * @ORM\Column(type="string")
   * @var string
   */
  private $confirmed_ip;

  /**
   * @ORM\Column(type="datetimetz")
   * @var DateTimeInterface|null
   */
  private $confirmed_at;

  /**
   * @ORM\Column(type="datetimetz")
   * @var DateTimeInterface|null
   */
  private $last_subscribed_at;

  /**
   * @ORM\Column(type="text")
   * @var string|null
   */
  private $unconfirmed_data;

  /**
   * @ORM\Column(type="string")
   * @var string|null
   */
  private $source = 'unknown';

  /**
   * @ORM\Column(type="int")
   * @var int|null
   */
  private $count_confirmations;

  /**
   * @ORM\Column(type="string")
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
    return $this->wp_user_id;
  }

  /**
   * @param int|null $wp_user_id
   */
  public function setWpUserId($wp_user_id) {
    $this->wp_user_id = $wp_user_id;
  }

  /**
   * @return bool|null
   */
  public function getIsWoocommerceUser() {
    return $this->is_woocommerce_user;
  }

  /**
   * @param bool|null $is_woocommerce_user
   */
  public function setIsWoocommerceUser($is_woocommerce_user) {
    $this->is_woocommerce_user = $is_woocommerce_user;
  }

  /**
   * @return string|null
   */
  public function getFirstName() {
    return $this->first_name;
  }

  /**
   * @param string|null $first_name
   */
  public function setFirstName($first_name) {
    $this->first_name = $first_name;
  }

  /**
   * @return string|null
   */
  public function getLastName() {
    return $this->last_name;
  }

  /**
   * @param string|null $last_name
   */
  public function setLastName($last_name) {
    $this->last_name = $last_name;
  }

  /**
   * @return string|null
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * @param string|null $email
   */
  public function setEmail($email) {
    $this->email = $email;
  }

  /**
   * @return string|null
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param string|null $status
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
    return $this->subscribed_ip;
  }

  /**
   * @param string|null $subscribed_ip
   */
  public function setSubscribedIp($subscribed_ip) {
    $this->subscribed_ip = $subscribed_ip;
  }

  /**
   * @return string|null
   */
  public function getConfirmedIp() {
    return $this->confirmed_ip;
  }

  /**
   * @param string|null $confirmed_ip
   */
  public function setConfirmedIp($confirmed_ip) {
    $this->confirmed_ip = $confirmed_ip;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getConfirmedAt() {
    return $this->confirmed_at;
  }

  /**
   * @param DateTimeInterface|null $confirmed_at
   */
  public function setConfirmedAt($confirmed_at) {
    $this->confirmed_at = $confirmed_at;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getLastSubscribedAt() {
    return $this->last_subscribed_at;
  }

  /**
   * @param DateTimeInterface|null $last_subscribed_at
   */
  public function setLastSubscribedAt($last_subscribed_at) {
    $this->last_subscribed_at = $last_subscribed_at;
  }

  /**
   * @return string|null
   */
  public function getUnconfirmedData() {
    return $this->unconfirmed_data;
  }

  /**
   * @param string|null $unconfirmed_data
   */
  public function setUnconfirmedData($unconfirmed_data) {
    $this->unconfirmed_data = $unconfirmed_data;
  }

  /**
   * @return string|null
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * @param string|null $source
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
   * @return int|null
   */
  public function getConfirmationsCount() {
    return $this->count_confirmations;
  }

  /**
   * @param int|null $count_confirmations
   */
  public function setConfirmationsCount($count_confirmations) {
    $this->count_confirmations = $count_confirmations;
  }

  /**
   * @return string|null
   */
  public function getUnsubscribeToken() {
    return $this->unsubscribe_token;
  }

  /**
   * @param string|null $unsubscribe_token
   */
  public function setUnsubscribeToken($unsubscribe_token) {
    $this->unsubscribe_token = $unsubscribe_token;
  }

  /**
   * @return string|null
   */
  public function getLinkToken() {
    return $this->link_token;
  }

  /**
   * @param string|null $link_token
   */
  public function setLinkToken($link_token) {
    $this->link_token = $link_token;
  }

}
