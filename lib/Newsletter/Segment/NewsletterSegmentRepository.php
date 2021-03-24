<?php

namespace MailPoet\Newsletter\Segment;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;

/**
 * @extends Repository<NewsletterSegmentEntity>
 */
class NewsletterSegmentRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterSegmentEntity::class;
  }

  public function getSubjectsOfActivelyUsedEmailsForSegments(array $segmentIds): array {
    $results = $this->doctrineRepository->createQueryBuilder('ns')
      ->join('ns.newsletter', 'n')
      ->leftJoin('n.queues', 'q')
      ->leftJoin('q.task', 't')
      ->select('IDENTITY(ns.segment) AS segment_id, n.subject')
      ->where('(n.type IN (:types) OR n.status = :scheduled OR (t.id IS NOT NULL AND t.status IS NULL))')
      ->andWhere('ns.segment IN (:segmentIds)')
      ->setParameter('types', [
        NewsletterEntity::TYPE_AUTOMATIC,
        NewsletterEntity::TYPE_WELCOME,
        NewsletterEntity::TYPE_NOTIFICATION,
      ])
      ->setParameter('segmentIds', $segmentIds)
      ->setParameter('scheduled', NewsletterEntity::STATUS_SCHEDULED)
      ->addGroupBy('n.id, q.id, t.id')
      ->getQuery()
      ->getResult();

    $nameMap = [];
    foreach ($results as $result) {
      $nameMap[(string)$result['segment_id']][] = $result['subject'];
    }
    return $nameMap;
  }
}
