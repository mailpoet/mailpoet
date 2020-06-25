<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscriberSegmentRepository;

class SubscribersResponseBuilder {

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function __construct(SubscriberSegmentRepository $subscriberSegmentRepository) {
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
  }

  public function build(SubscriberEntity $subscriberEntity): array {
    $data = [
      'id' => $subscriberEntity->getId(),
      'wp_user_id' => $subscriberEntity->getWpUserId(),
      'is_woocommerce_user' => $subscriberEntity->getIsWoocommerceUser(),
      'subscriptions' => $this->buildSubscriptions($subscriberEntity),
      'unsubscribes' => [],// TODO
      // TODO custom fields
      'status' => $subscriberEntity->getStatus(),
      'last_name' => $subscriberEntity->getLastName(),
      'first_name' => $subscriberEntity->getFirstName(),
      'email' => $subscriberEntity->getEmail(),
    ];

    return $data;
  }

  private function buildSubscriptions(SubscriberEntity $subscriberEntity): array {
    $result = [];
    $subscriptions = $this->subscriberSegmentRepository->findAll(['subscriber' => $subscriberEntity]);
    foreach ($subscriptions as $subscription) {
      $segment = $subscription->getSegment();
      if ($segment instanceof SegmentEntity) {
        $result[] = [
          'segment_id' => $segment->getId(),
          'status' => $subscription->getStatus(),
          'updated_at' => $subscription->getUpdatedAt(),
        ];
      }
    }
    return $result;
  }
}
