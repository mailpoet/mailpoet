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

  public function getScheduledNewsletterSubjectsBySegmentIds(array $segmentIds): array {
    $results = $this->doctrineRepository->createQueryBuilder('ns')
      ->select('IDENTITY(ns.segment) AS segment_id, n.subject')
      ->join('ns.newsletter', 'n')
      ->where('n.status = :scheduled')
      ->andWhere('ns.segment IN (:segmentIds)')
      ->setParameter('scheduled', NewsletterEntity::STATUS_SCHEDULED)
      ->setParameter('segmentIds', $segmentIds)
      ->getQuery()
      ->getResult();
    
    $nameMap = [];
    foreach ($results as $result) {
      $nameMap[(string)$result['segment_id']][] = $result['subject'];
    }
    return $nameMap;
  }
}
