<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

if (!defined('ABSPATH')) exit;

class SubscriberLinkTokens extends SimpleWorker {
  const TASK_TYPE = 'subscriber_link_tokens';
  const BATCH_SIZE = 10000;
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const LAST_UPGRADED_SUBSCRIBER_ID_SETTING = 'obsolete_link_tokens_last_upgraded_subscriber_id';

  /** @var EntityManager */
  private $entityManager;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    EntityManager $entityManager,
    LinkTokens $linkTokens,
    SettingsController $settings
  ) {
    parent::__construct();
    $this->entityManager = $entityManager;
    $this->linkTokens = $linkTokens;
    $this->settings = $settings;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    // Read before filling missing tokens, so every subscriber up to this id has a token when the walk below passes it
    $maxSubscriberId = $this->getMaxSubscriberId();
    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $updatedCount = $this->addMissingTokens();
    } while ($updatedCount === self::BATCH_SIZE);

    $this->upgradeObsoleteTokens($maxSubscriberId, $timer);
    return true;
  }

  public function getNextRunDate() {
    return Carbon::now()->millisecond(0);
  }

  private function addMissingTokens(): int {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    // Hashing a one-off random secret with the row id gives every subscriber a random 32-character token in one query
    return (int)$this->entityManager->getConnection()->executeStatement(
      "UPDATE {$subscribersTable} SET link_token = MD5(CONCAT(:secret, id)) WHERE link_token IS NULL LIMIT :limit",
      ['secret' => bin2hex(random_bytes(32)), 'limit' => self::BATCH_SIZE],
      ['secret' => ParameterType::STRING, 'limit' => ParameterType::INTEGER]
    );
  }

  private function getMaxSubscriberId(): int {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $maxSubscriberId = $this->entityManager->getConnection()->fetchOne("SELECT MAX(id) FROM {$subscribersTable}");
    return is_numeric($maxSubscriberId) ? (int)$maxSubscriberId : 0;
  }

  private function upgradeObsoleteTokens(int $maxSubscriberId, $timer): void {
    $this->linkTokens->startObsoleteTokensGracePeriod();

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    // Link tokens are never cleared, so only subscribers added since the last run can have an obsolete token,
    // e.g. ones created while an older plugin version was active
    $lastSubscriberId = $this->settings->get(self::LAST_UPGRADED_SUBSCRIBER_ID_SETTING, 0);
    $lastSubscriberId = is_numeric($lastSubscriberId) ? (int)$lastSubscriberId : 0;

    // Walking primary key ranges keeps each batch cheap, as the token length can't use an index
    while ($lastSubscriberId < $maxSubscriberId) {
      $this->cronHelper->enforceExecutionLimit($timer);
      $nextSubscriberId = $lastSubscriberId + self::BATCH_SIZE;
      $connection->executeStatement(
        "UPDATE {$subscribersTable}
        SET link_token = CONCAT(link_token, :separator, SUBSTRING(MD5(CONCAT(:secret, id)), 1, :suffixLength))
        WHERE id > :lastSubscriberId AND id <= :nextSubscriberId AND CHAR_LENGTH(link_token) = :obsoleteLength",
        [
          'separator' => LinkTokens::UPGRADED_TOKEN_SEPARATOR,
          'secret' => bin2hex(random_bytes(32)),
          'suffixLength' => SubscriberEntity::LINK_TOKEN_LENGTH - SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH - 1,
          'lastSubscriberId' => $lastSubscriberId,
          'nextSubscriberId' => $nextSubscriberId,
          'obsoleteLength' => SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH,
        ],
        [
          'separator' => ParameterType::STRING,
          'secret' => ParameterType::STRING,
          'suffixLength' => ParameterType::INTEGER,
          'lastSubscriberId' => ParameterType::INTEGER,
          'nextSubscriberId' => ParameterType::INTEGER,
          'obsoleteLength' => ParameterType::INTEGER,
        ]
      );
      $lastSubscriberId = min($nextSubscriberId, $maxSubscriberId);
      $this->settings->set(self::LAST_UPGRADED_SUBSCRIBER_ID_SETTING, $lastSubscriberId);
    }
  }
}
