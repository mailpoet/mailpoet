<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

if (!defined('ABSPATH')) exit;

class SubscriberLinkTokens extends SimpleWorker {
  const TASK_TYPE = 'subscriber_link_tokens';
  const BATCH_SIZE = 10000;
  const AUTOMATIC_SCHEDULING = false;

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $subscribersRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);
    $subscribersTable = $entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $connection = $entityManager->getConnection();

    $count = $subscribersRepository->countBy(['linkToken' => null]);

    if ($count) {
      // Hashing a one-off random secret with the row id gives every subscriber a random 32-character token in one query
      $secret = bin2hex(random_bytes(32));

      $connection->executeStatement(
        "UPDATE {$subscribersTable} SET link_token = MD5(CONCAT(:secret, id)) WHERE link_token IS NULL LIMIT :limit",
        ['secret' => $secret, 'limit' => self::BATCH_SIZE],
        ['secret' => ParameterType::STRING, 'limit' => ParameterType::INTEGER]
      );

      $this->schedule();
    }
    return true;
  }

  public function getNextRunDate() {
    return Carbon::now()->millisecond(0);
  }
}
