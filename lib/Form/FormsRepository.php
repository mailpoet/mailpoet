<?php

namespace MailPoet\Form;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\FormEntity;
use MailPoetVendor\Carbon\Carbon;

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

  public function count(): int {
    return (int)$this->doctrineRepository
      ->createQueryBuilder('f')
      ->select('count(f.id)')
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function trash(FormEntity $form) {
    $form->setDeletedAt(Carbon::now());
    $this->persist($form);
    $this->flush();
  }
}
