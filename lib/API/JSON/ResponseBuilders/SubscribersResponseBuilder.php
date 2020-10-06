<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    CustomFieldsRepository $customFieldsRepository,
    SubscriberCustomFieldRepository $subscriberCustomFieldRepository,
    StatisticsUnsubscribesRepository $statisticsUnsubscribesRepository
  ) {
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->statisticsUnsubscribesRepository = $statisticsUnsubscribesRepository;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->subscriberCustomFieldRepository = $subscriberCustomFieldRepository;
    $this->entityManager = $entityManager;
  }

  public function buildForListing(array $subscribers): array {
    $this->prefetchSegments($subscribers);
    $data = [];
    foreach ($subscribers as $subscriber) {
      $data[] = $this->buildListingItem($subscriber);
    }
    return $data;
  }

  private function buildListingItem(SubscriberEntity $subscriber): array {
    return [
      'id' => (string)$subscriber->getId(), // (string) for BC
      'email' => $subscriber->getEmail(),
      'first_name' => $subscriber->getFirstName(),
      'last_name' => $subscriber->getLastName(),
      'subscriptions' => $this->buildSubscriptions($subscriber),
      'status' => $subscriber->getStatus(),
      'count_confirmations' => $subscriber->getConfirmationsCount(),
      'wp_user_id' => $subscriber->getWpUserId(),
      'is_woocommerce_user' => $subscriber->getIsWoocommerceUser(),
      'created_at' => $subscriber->getCreatedAt()->format(self::DATE_FORMAT),
    ];
  }

  public function build(SubscriberEntity $subscriberEntity): array {
    $data = [
      'id' => (string)$subscriberEntity->getId(),
      'wp_user_id' => $subscriberEntity->getWpUserId(),
      'is_woocommerce_user' => $subscriberEntity->getIsWoocommerceUser(),
      'subscriptions' => $this->buildSubscriptions($subscriberEntity),
      'unsubscribes' => $this->buildUnsubscribes($subscriberEntity),
      'status' => $subscriberEntity->getStatus(),
      'last_name' => $subscriberEntity->getLastName(),
      'first_name' => $subscriberEntity->getFirstName(),
      'email' => $subscriberEntity->getEmail(),
      'deleted_at' => ($deletedAt = $subscriberEntity->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null, 
    ];
    $data = $this->buildCustomFields($subscriberEntity, $data);
    return $data;
  }

  private function buildSubscriptions(SubscriberEntity $subscriberEntity): array {
    $result = [];
    $subscriptions = $this->subscriberSegmentRepository->findBy(['subscriber' => $subscriberEntity]);
    foreach ($subscriptions as $subscription) {
      $segment = $subscription->getSegment();
      if ($segment instanceof SegmentEntity) {
        $result[] = [
          'segment_id' => (string)$segment->getId(),
          'status' => $subscription->getStatus(),
          'updated_at' => $subscription->getUpdatedAt()->format(self::DATE_FORMAT),
        ];
      }
    }
    return $result;
  }

  private function buildUnsubscribes(SubscriberEntity $subscriberEntity): array {
    $unsubscribes = $this->statisticsUnsubscribesRepository->findBy([
      'subscriber' => $subscriberEntity,
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

  private function buildCustomFields(SubscriberEntity $subscriberEntity, array $data): array {
    $customFields = $this->customFieldsRepository->findAll();

    foreach ($customFields as $customField) {
      $subscriberCustomField = $this->subscriberCustomFieldRepository->findOneBy(
        ['subscriber' => $subscriberEntity, 'customField' => $customField]
      );
      if ($subscriberCustomField instanceof SubscriberCustomFieldEntity) {
        $data['cf_' . $customField->getId()] = $subscriberCustomField->getValue();
      }
    }
    return $data;
  }

  /**
   * @param SubscriberEntity[] $subscribers
   */
  private function prefetchSegments(array $subscribers) {
    $this->entityManager->createQueryBuilder()
      ->select('PARTIAL s.{id}, ssg, sg')
      ->from(SubscriberEntity::class, 's')
      ->join('s.subscriberSegments', 'ssg')
      ->join('ssg.segment', 'sg')
      ->where('s.id IN (:subscribers)')
      ->setParameter('subscribers', $subscribers)
      ->getQuery()
      ->getResult();
  }
}
