<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;

class SubscribersResponseBuilder {

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  public function __construct(
    SubscriberSegmentRepository $subscriberSegmentRepository,
    StatisticsUnsubscribesRepository $statisticsUnsubscribesRepository
  ) {
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->statisticsUnsubscribesRepository = $statisticsUnsubscribesRepository;
  }

  public function build(SubscriberEntity $subscriberEntity): array {
    $data = [
      'id' => $subscriberEntity->getId(),
      'wp_user_id' => $subscriberEntity->getWpUserId(),
      'is_woocommerce_user' => $subscriberEntity->getIsWoocommerceUser(),
      'subscriptions' => $this->buildSubscriptions($subscriberEntity),
      'unsubscribes' => $this->buildUnsubscribes($subscriberEntity),
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
    $subscriptions = $this->subscriberSegmentRepository->findBy(['subscriber' => $subscriberEntity]);
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

  private function buildUnsubscribes(SubscriberEntity $subscriberEntity): array {
    $unsubscribes = $this->statisticsUnsubscribesRepository->findBy([
      'subscriberId' => $subscriberEntity->getId(),
    ], [
      'createdAt' => 'desc',
    ]);
    $result = [];
    foreach ($unsubscribes as $unsubscribe) {
      $mapped = [
        'source' => $unsubscribe->getSource(),
        'meta' => $unsubscribe->getMeta(),
        'createdAt' => $unsubscribe->getCreatedAt(),
      ];
      $newsletter = $unsubscribe->getNewsletter();
      if ($newsletter instanceof NewsletterEntity) {
        $mapped['newsletterId'] = $newsletter->getId();
        $mapped['newsletterSubject'] = $newsletter->getSubject();
      }
      $result[] = $mapped;
    }
    return $result;
  }
}
