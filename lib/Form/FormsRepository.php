<?php

namespace MailPoet\Form;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\FormEntity;

/**
 * @extends Repository<FormEntity>
 */
class FormsRepository extends Repository {
  protected function getEntityClassName() {
    return FormEntity::class;
  }

  /**
   * @return FormEntity[]
   */
  public function findAllNotDeleted(): array {
    return $this->entityManager
      ->createQueryBuilder()
      ->select('f')
      ->from(FormEntity::class, 'f')
      ->where('f.deletedAt IS NULL')
      ->orderBy('f.updatedAt', 'desc')
      ->getQuery()
      ->getResult();
  }
}
