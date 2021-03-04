<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\SubscriberSegment;

class Subscriber {

  /** @var array */
  private $data;

  /** @var SegmentEntity[] */
  private $segments;

  public function __construct() {
    $this->data = [
      'email' => bin2hex(random_bytes(7)) . '@example.com', // phpcs:ignore
      'status' => 'subscribed',
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

  /**
   * @param int $count
   * @return $this
   */
  public function withCountConfirmations($count) {
    $this->data['count_confirmations'] = $count;
    return $this;
  }

  /**
   * @param SegmentEntity[] $segments
   * @return $this
   */
  public function withSegments(array $segments) {
    $this->segments = array_merge($this->segments, $segments);
    return $this;
  }

  /**
   * @return \MailPoet\Models\Subscriber
   * @throws \Exception
   */
  public function create() {
    $subscriber = \MailPoet\Models\Subscriber::createOrUpdate($this->data);
    foreach ($this->segments as $segment) {
      SubscriberSegment::createOrUpdate([
        'subscriber_id' => $subscriber->id(),
        'segment_id' => $segment->getId(),
      ]);
    }
    return $subscriber;
  }
}
