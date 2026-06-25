<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class UnsubscribeTokens extends SimpleWorker {
  const TASK_TYPE = 'unsubscribe_tokens';
  const BATCH_SIZE = 1000;
  const AUTOMATIC_SCHEDULING = false;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    parent::__construct();
    $this->entityManager = $entityManager;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    foreach ([SubscriberEntity::class, NewsletterEntity::class] as $entityClass) {
      do {
        $this->cronHelper->enforceExecutionLimit($timer);
        $updatedCount = $this->addTokens($entityClass);
      } while ($updatedCount === self::BATCH_SIZE);
    }
    return true;
  }

  /**
   * @param class-string $entityClass
   */
  private function addTokens(string $entityClass): int {
    $tableName = $this->entityManager->getClassMetadata($entityClass)->getTableName();
    $connection = $this->entityManager->getConnection();
    $authKey = defined('AUTH_KEY') ? AUTH_KEY : '';

    // A direct UPDATE keeps the backfill out of Doctrine's UnitOfWork: changes made to
    // PARTIAL-hydrated entities are not registered, so the previous entity-based approach
    // computed an empty changeset and silently wrote nothing. The token is derived from
    // AUTH_KEY (so it stays unguessable) and the row id salted per entity type (so it stays
    // unique within its table and never collides across the two tables).
    return (int)$connection->executeStatement(
      "UPDATE {$tableName} SET unsubscribe_token = SUBSTRING(MD5(CONCAT(:authKey, :salt, id)), 1, :tokenLength) WHERE unsubscribe_token IS NULL LIMIT :limit",
      [
        'authKey' => $authKey,
        'salt' => $entityClass,
        'tokenLength' => Security::UNSUBSCRIBE_TOKEN_LENGTH,
        'limit' => self::BATCH_SIZE,
      ],
      [
        'authKey' => ParameterType::STRING,
        'salt' => ParameterType::STRING,
        'tokenLength' => ParameterType::INTEGER,
        'limit' => ParameterType::INTEGER,
      ]
    );
  }

  public function getNextRunDate() {
    return Carbon::now()->millisecond(0);
  }
}
