<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Migrator\AppMigration;

/**
 * The plugin from the version 5.5.2 to 5.6.1 contained a bug when we stored links containing &amp; and in some cases also links with `&amp;amp;` in the database.
 * This migration fixes the issue by replacing `&amp;amp;` with `&amp; and then &amp; with &`.
 *
 * See https://mailpoet.atlassian.net/browse/MAILPOET-6433
 */
class Migration_20250120_094614_App extends AppMigration {
  public function run(): void {
    $sendingQueueId = $this->getSendingQueueId();
    if ($sendingQueueId) {
      $linksTable = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
      $this->entityManager->getConnection()->executeQuery("
        UPDATE {$linksTable}
        SET url = REPLACE( REPLACE(url, '&amp;amp;', '&amp;'), '&amp;', '&')
        WHERE queue_id >= :queue_id;
      ", ['queue_id' => $sendingQueueId]);
    }
  }

  private function getSendingQueueId(): ?int {
    $qb = $this->entityManager->createQueryBuilder();
    /** @var array{id: number}|null $result */
    $result = $qb->select('sq.id AS id')
      ->from(SendingQueueEntity::class, 'sq')
      ->where(
        $qb->expr()->gt('sq.createdAt', ':date')
      )
      ->orderBy('sq.id', 'ASC')
      ->setMaxResults(1)
      ->setParameter('date', '2024-12-24:00:00:00')
      ->getQuery()
      ->getOneOrNullResult();
    return $result ? (int)$result['id'] : null;
  }
}
