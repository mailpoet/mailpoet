<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<NewsletterEntity>
 */
class AutomaticEmailsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  public function wasScheduledForSubscriber(int $newsletterId, int $subscriberId): bool {
    $query = $this->doctrineRepository->createQueryBuilder('n')
      ->select('COUNT(q)')
      ->from(SendingQueueEntity::class, 'q');
    $query = $this->getAllQueuesForSubscscriberQuery($query, $newsletterId, $subscriberId);
    $count = $query->getQuery()
      ->getSingleScalarResult() ?: 0;
    return ((int)$count) > 0;
  }

  private function getAllQueuesForSubscscriberQuery(QueryBuilder $query, int $newsletterId, int $subscriberId): QueryBuilder {
    // A recipient lives in the queue while the task is pending and moves to the
    // processed log once it is sent, so the subscriber may be in either table.
    return $query
      ->join('q.task', 't')
      ->andWhere('q.newsletter = :newsletterId')
      ->andWhere(
        'EXISTS (SELECT 1 FROM ' . ScheduledTaskSubscriberEntity::class . ' sts WHERE sts.task = t AND sts.subscriber = :subscriberId)'
        . ' OR EXISTS (SELECT 1 FROM ' . ScheduledTaskQueuedSubscriberEntity::class . ' stsq WHERE stsq.task = t AND stsq.subscriber = :subscriberId)'
      )
      ->setParameter('newsletterId', $newsletterId)
      ->setParameter('subscriberId', $subscriberId);
  }

  /**
   * Search products/categories in meta if all of the ordered products have already been sent to the subscriber.
   */
  public function alreadySentAllProducts(int $newsletterId, int $subscriberId, string $orderedKey, array $ordered): bool {
    $query = $this->doctrineRepository->createQueryBuilder('n')
      ->select('q')
      ->from(SendingQueueEntity::class, 'q');
    $queues = $this->getAllQueuesForSubscscriberQuery($query, $newsletterId, $subscriberId)
      ->getQuery()
      ->getResult();
    $sent = [];
    foreach ($queues as $queue) {
      $meta = $queue->getMeta();
      if (isset($meta[$orderedKey])) {
        $sent = array_merge($sent, $meta[$orderedKey]);
      }
    }
    $notSentProducts = array_diff($ordered, $sent);

    return empty($notSentProducts);
  }
}
