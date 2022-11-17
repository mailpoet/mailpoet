<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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

  public function getNamesOfFormsForSegments(): array {
    $allNonDeletedForms = $this->findAllNotDeleted();

    $nameMap = [];
    foreach ($allNonDeletedForms as $form) {
      $blockSegmentsIds = $form->getSettingsSegmentIds();
      foreach ($blockSegmentsIds as $blockSegmentId) {
        $nameMap[$blockSegmentId][] = $form->getName();
      }
    }

    return $nameMap;
  }

  public function count(): int {
    return (int)$this->doctrineRepository
      ->createQueryBuilder('f')
      ->select('count(f.id)')
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function delete(FormEntity $form) {
    $this->entityManager->remove($form);
    $this->flush();
  }

  public function trash(FormEntity $form) {
    $this->bulkTrash([$form->getId()]);
    $this->entityManager->refresh($form);
  }

  public function restore(FormEntity $form) {
    $this->bulkRestore([$form->getId()]);
    $this->entityManager->refresh($form);
  }

  public function bulkTrash(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    return $this->entityManager->createQueryBuilder()
      ->update(FormEntity::class, 'f')
      ->set('f.deletedAt', 'CURRENT_TIMESTAMP()')
      ->where('f.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();
  }

  public function bulkRestore(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    return $this->entityManager->createQueryBuilder()
      ->update(FormEntity::class, 'f')
      ->set('f.deletedAt', ':deletedAt')
      ->where('f.id IN (:ids)')
      ->setParameter('deletedAt', null)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();
  }

  public function bulkDelete(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    return $this->entityManager->createQueryBuilder()
      ->delete(FormEntity::class, 'f')
      ->where('f.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();
  }
}
