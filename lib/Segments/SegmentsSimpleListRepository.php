<?php

namespace MailPoet\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SegmentsSimpleListRepository {
  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentSubscribersRepository */
  private $segmentsSubscriberRepository;

  public function __construct(
    EntityManager $entityManager,
    SegmentSubscribersRepository $segmentsSubscriberRepository
  ) {
    $this->entityManager = $entityManager;
    $this->segmentsSubscriberRepository = $segmentsSubscriberRepository;
  }

  /**
   * This fetches list of all segments basic data and count of subscribed subscribers.
   * @return array<array{id: string, name: string, type: string, subscribers: int}>
   */
  public function getListWithSubscribedSubscribersCounts(array $segmentTypes = []): array {
    return $this->getList(
      $segmentTypes,
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_SUBSCRIBED
    );
  }

  /**
   * This fetches list of all segments basic data and count of subscribers associated to a segment regardless their subscription status.
   * @return array<array{id: string, name: string, type: string, subscribers: int}>
   */
  public function getListWithAssociatedSubscribersCounts(array $segmentTypes = []): array {
    return $this->getList(
      $segmentTypes
    );
  }

  /**
   * Adds a virtual segment with for subscribers without list
   * @return array<array{id: string, name: string, type: string, subscribers: int}>
   */
  public function addVirtualSubscribersWithoutListSegment(array $segments): array {
    $segments[] = [
      'id' => '0',
      'type' => SegmentEntity::TYPE_WITHOUT_LIST,
      'name' => __('Not in a List', 'mailpoet'),
      'subscribers' => $this->segmentsSubscriberRepository->getSubscribersWithoutSegmentCount(),
    ];
    return $segments;
  }

  /**
   * @return array<array{id: string, name: string, type: string, subscribers: int}>
   */
  private function getList(
    array $segmentTypes = [],
    string $subscriberGlobalStatus = null,
    string $subscriberSegmentStatus = null
  ): array {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscribersSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();

    $segmentsDataQuery = $this->entityManager
      ->getConnection()
      ->createQueryBuilder();

    $countCondition = "subscribers.deleted_at IS NULL AND subsegments.id IS NOT NULL AND subscribers.id IS NOT NULL";
    if ($subscriberGlobalStatus) {
      $countCondition .= " AND subscribers.status= :subscriberGlobalStatus";
      $segmentsDataQuery->setParameter('subscriberGlobalStatus', $subscriberGlobalStatus);
    }

    if ($subscriberSegmentStatus) {
      $countCondition .= " AND subsegments.status = :subscriberSegmentStatus";
      $segmentsDataQuery->setParameter('subscriberSegmentStatus', $subscriberSegmentStatus);
    }

    $segmentsDataQuery->select(
        "segments.id, segments.name, segments.type, COUNT(IF($countCondition, 1, NULL)) as subscribers"
      )->from($segmentsTable, 'segments')
      ->leftJoin('segments', $subscribersSegmentsTable, 'subsegments', "subsegments.segment_id = segments.id")
      ->leftJoin('subsegments', $subscribersTable, 'subscribers', "subscribers.id = subsegments.subscriber_id")
      ->where('segments.deleted_at IS NULL')
      ->groupBy('segments.id')
      ->addGroupBy('segments.name')
      ->addGroupBy('segments.type')
      ->orderBy('segments.name');

    if (!empty($segmentTypes)) {
      $segmentsDataQuery
        ->andWhere('segments.type IN (:typesParam)')
        ->setParameter('typesParam', $segmentTypes, Connection::PARAM_STR_ARRAY);
    }

    $statement = $segmentsDataQuery->execute();
    if (!$statement instanceof Statement) {
      return [];
    }
    $segments = $statement->fetchAll();

    // Fetch subscribers counts for dynamic segments and correct data types
    foreach ($segments as $key => $segment) {
      if ($segment['type'] === SegmentEntity::TYPE_DYNAMIC) {
        $segments[$key]['subscribers'] = $this->segmentsSubscriberRepository->getSubscribersCount((int)$segment['id'], $subscriberGlobalStatus);
      } else {
        $segments[$key]['subscribers'] = (int)$segment['subscribers'];
      }
    }
    return $segments;
  }
}
