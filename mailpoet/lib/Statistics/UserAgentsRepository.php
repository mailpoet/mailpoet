<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\UserAgentEntity;

/**
 * @extends Repository<UserAgentEntity>
 */
class UserAgentsRepository extends Repository {
  protected function getEntityClassName() {
    return UserAgentEntity::class;
  }

  public function findOrCreate(string $userAgent): UserAgentEntity {
    $hash = (string)crc32($userAgent);
    $userAgentEntity = $this->findOneBy(['hash' => $hash]);
    return $userAgentEntity ?? $this->create($userAgent);
  }

  public function create(string $userAgent): UserAgentEntity {
    $userAgentEntity = new UserAgentEntity($userAgent);

    $this->entityManager->getConnection()->executeStatement(
      'INSERT INTO ' . $this->getTableName() . ' (user_agent, hash) VALUES (:user_agent, :hash) ON DUPLICATE KEY UPDATE id = id',
      [
        'user_agent' => $userAgentEntity->getUserAgent(),
        'hash' => $userAgentEntity->getHash(),
      ]
    );

    /** @var UserAgentEntity $userAgentEntity */
    $userAgentEntity = $this->findOneBy(['hash' => $userAgentEntity->getHash()]);
    return $userAgentEntity;
  }
}
