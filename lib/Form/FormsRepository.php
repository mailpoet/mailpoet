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
    $this->updateDeletedAt($form, Carbon::now());
  }

  public function restore(FormEntity $form) {
    $this->updateDeletedAt($form, null);
  }

  private function updateDeletedAt(FormEntity $form, ?Carbon $value) {
    $form->setDeletedAt($value);
    $this->persist($form);
    $this->flush();
  }
}
