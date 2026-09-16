<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<StatisticsClickEntity>
 */
class StatisticsClicksRepository extends Repository {
  /** @var StatisticsNewslettersRepository */
  private $statisticsNewslettersRepository;

  public function __construct(
    EntityManager $entityManager,
    StatisticsNewslettersRepository $statisticsNewslettersRepository
  ) {
    parent::__construct($entityManager);
    $this->statisticsNewslettersRepository = $statisticsNewslettersRepository;
  }

  protected function getEntityClassName(): string {
    return StatisticsClickEntity::class;
  }

  public function createOrUpdateClickCount(
    NewsletterLinkEntity $link,
    SubscriberEntity $subscriber,
    NewsletterEntity $newsletter,
    SendingQueueEntity $queue,
    ?UserAgentEntity $userAgent
  ): StatisticsClickEntity {
    $statistics = $this->findOneBy([
      'link' => $link,
      'newsletter' => $newsletter,
      'subscriber' => $subscriber,
      'queue' => $queue,
    ]);
    if (!$statistics instanceof StatisticsClickEntity) {
      $statistics = new StatisticsClickEntity($newsletter, $queue, $subscriber, $link, 1);
      if ($userAgent) {
        $statistics->setUserAgent($userAgent);
        $statistics->setUserAgentType($userAgent->getUserAgentType());
      }
      $this->persist($statistics);
    } else {
      $statistics->setCount($statistics->getCount() + 1);
    }
    // Every writer of a click row goes through here, including the one-click unsubscribe in
    // Subscription\Pages, which never reaches the tracking endpoint.
    $this->statisticsNewslettersRepository->markSentWithTracking($newsletter, $queue, $subscriber);
    return $statistics;
  }

  public function getAllForSubscriber(SubscriberEntity $subscriber): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('clicks.id id, queue.newsletterRenderedSubject, clicks.createdAt, link.url, userAgent.userAgent')
      ->from(StatisticsClickEntity::class, 'clicks')
      ->join('clicks.queue', 'queue')
      ->join('clicks.link', 'link')
      ->leftJoin('clicks.userAgent', 'userAgent')
      ->where('clicks.subscriber = :subscriber')
      ->orderBy('link.url')
      ->setParameter('subscriber', $subscriber->getId());
  }

  /**
   * @param SubscriberEntity $subscriber
   * @param \DateTimeInterface $from
   * @param \DateTimeInterface $to
   * @return StatisticsClickEntity[]
   */
  public function findLatestPerNewsletterBySubscriber(SubscriberEntity $subscriber, \DateTimeInterface $from, \DateTimeInterface $to): array {
    // subquery to find latest click IDs for each newsletter
    $latestClickIdsPerNewsletterQuery = $this->entityManager->createQueryBuilder()
      ->select('MAX(clicks.id)')
      ->from(StatisticsClickEntity::class, 'clicks')
      ->where('clicks.subscriber = :subscriber')
      ->andWhere('clicks.updatedAt > :from')
      ->andWhere('clicks.updatedAt < :to')
      ->groupBy('clicks.newsletter');

    $expr = $this->entityManager->getExpressionBuilder();
    return $this->entityManager->createQueryBuilder()
      ->select('c')
      ->from(StatisticsClickEntity::class, 'c')
      ->where(
        $expr->in(
          'c.id',
          $latestClickIdsPerNewsletterQuery->getDQL()
        )
      )
      ->setParameter('subscriber', $subscriber)
      ->setParameter('from', $from->format('Y-m-d H:i:s'))
      ->setParameter('to', $to->format('Y-m-d H:i:s'))
      ->getQuery()
      ->getResult();
  }

  /** @param int[] $ids */
  public function deleteByNewsletterIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(StatisticsClickEntity::class, 's')
      ->where('s.newsletter IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (StatisticsClickEntity $entity) use ($ids) {
      $newsletter = $entity->getNewsletter();
      return $newsletter && in_array($newsletter->getId(), $ids, true);
    });
  }
}
