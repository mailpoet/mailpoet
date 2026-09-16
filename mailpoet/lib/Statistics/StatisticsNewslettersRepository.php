<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Exception as DBALException;
use MailPoetVendor\Doctrine\DBAL\Exception\InvalidFieldNameException;

/**
 * @extends Repository<StatisticsNewsletterEntity>
 */
class StatisticsNewslettersRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsNewsletterEntity::class;
  }

  public function createMultiple(array $data): void {
    $entities = [];

    foreach ($data as $value) {
      if (!empty($value['newsletter_id']) && !empty($value['queue_id']) && !empty($value['subscriber_id'])) {
        $newsletter = $this->entityManager->getReference(NewsletterEntity::class, $value['newsletter_id']);
        $queue = $this->entityManager->getReference(SendingQueueEntity::class, $value['queue_id']);
        $subscriber = $this->entityManager->getReference(SubscriberEntity::class, $value['subscriber_id']);

        if (!$newsletter || !$queue || !$subscriber) {
          continue;
        }

        $sentAt = Carbon::now()->millisecond(0);
        $entity = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber, $sentAt);

        $this->entityManager->persist($entity);
        $entities[] = $entity;
      }
    }

    if (count($entities)) {
      $this->entityManager->flush();
    }
    $this->markSentWithoutTracking($data);
  }

  /**
   * Rows default to sent_with_tracking = 1, so only rows sent without tracking are updated.
   * The column is not mapped on the entity: the emails are already out when this runs, so a
   * missing column or any database error must not stop the worker. The row then keeps 1.
   */
  private function markSentWithoutTracking(array $data): void {
    $subscriberIdsByQueue = [];
    foreach ($data as $value) {
      if (empty($value['newsletter_id']) || empty($value['queue_id']) || empty($value['subscriber_id'])) {
        continue;
      }
      if (!array_key_exists('sent_with_tracking', $value) || $value['sent_with_tracking']) {
        continue;
      }
      $key = (int)$value['newsletter_id'] . ':' . (int)$value['queue_id'];
      $subscriberIdsByQueue[$key][] = (int)$value['subscriber_id'];
    }
    if (!$subscriberIdsByQueue) {
      return;
    }

    $table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      foreach ($subscriberIdsByQueue as $key => $subscriberIds) {
        [$newsletterId, $queueId] = array_map('intval', explode(':', $key));
        $this->entityManager->getConnection()->executeStatement(
          "UPDATE `{$table}` SET sent_with_tracking = 0
           WHERE newsletter_id = :newsletterId AND queue_id = :queueId AND subscriber_id IN (:subscriberIds)",
          ['newsletterId' => $newsletterId, 'queueId' => $queueId, 'subscriberIds' => $subscriberIds],
          ['subscriberIds' => ArrayParameterType::INTEGER]
        );
      }
    } catch (InvalidFieldNameException $e) {
      return;
    } catch (DBALException $e) {
      try {
        LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
          'Could not mark sent statistics rows as sent without tracking',
          ['error' => $e->getMessage()]
        );
      } catch (\Throwable $loggingError) {
        // The database is likely what failed, so the log write can fail too.
      }
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
  }

  /**
   * An open or click recorded for a recipient whose email went out without tracking (for example
   * through the web version after they allowed tracking, or a one-click unsubscribe) would count
   * them in the open and click rates but not in the recipients those rates divide by, so their
   * sent row becomes tracked.
   * Runs on the tracking endpoint: a database error must not break the open or the click redirect.
   */
  public function markSentWithTracking(NewsletterEntity $newsletter, SendingQueueEntity $queue, SubscriberEntity $subscriber): void {
    $table = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      $this->entityManager->getConnection()->executeStatement(
        "UPDATE `{$table}` SET sent_with_tracking = 1, sent_at = sent_at
         WHERE newsletter_id = :newsletterId AND queue_id = :queueId AND subscriber_id = :subscriberId
           AND sent_with_tracking = 0",
        [
          'newsletterId' => $newsletter->getId(),
          'queueId' => $queue->getId(),
          'subscriberId' => $subscriber->getId(),
        ]
      );
    } catch (DBALException $e) {
      return;
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
  }

  /** @param int[] $ids */
  public function deleteByNewsletterIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(StatisticsNewsletterEntity::class, 's')
      ->where('s.newsletter IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (StatisticsNewsletterEntity $entity) use ($ids) {
      $newsletter = $entity->getNewsletter();
      return $newsletter && in_array($newsletter->getId(), $ids, true);
    });
  }
}
