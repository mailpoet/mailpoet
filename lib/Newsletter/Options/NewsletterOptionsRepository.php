<?php

namespace MailPoet\Newsletter\Options;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;

/**
 * @extends Repository<NewsletterOptionEntity>
 */
class NewsletterOptionsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterOptionEntity::class;
  }

  public function findWelcomeNotificationsForSegments(array $segmentIds): array {
    return $this->entityManager->createQueryBuilder()
      ->select('no')
      ->from(NewsletterOptionEntity::class, 'no')
      ->join('no.newsletter', 'n')
      ->join('no.optionField', 'nof')
      ->where('n.deletedAt IS NULL')
      ->andWhere('n.type = :typeWelcome')
      ->andWhere('nof.name = :nameSegment')
      ->andWhere('no.value IN (:segmentIds)')
      ->setParameter('typeWelcome', NewsletterEntity::TYPE_WELCOME)
      ->setParameter('nameSegment', NewsletterOptionFieldEntity::NAME_SEGMENT)
      ->setParameter('segmentIds', $segmentIds)
      ->getQuery()->getResult();
  }
}
