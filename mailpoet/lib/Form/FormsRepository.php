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

  /**
   * @return FormEntity[]
   */
  public function findAllActive(): array {
    return $this->entityManager
      ->createQueryBuilder()
      ->select('f')
      ->from(FormEntity::class, 'f')
      ->where('f.deletedAt IS NULL')
      ->andWhere('f.status = :status')
      ->setParameter('status', FormEntity::STATUS_ENABLED)
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

    // Sort form names alphabetically for each segment
    foreach ($nameMap as &$names) {
      sort($names);
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

  public function getActiveFormsCountByType(): array {
    $forms = $this->findAllActive();
    $formBlocks = $this->getActiveFormsBlocks();

    $counts = [
      'all' => count($forms),
      FormEntity::DISPLAY_TYPE_BELOW_POST => 0,
      FormEntity::DISPLAY_TYPE_FIXED_BAR => 0,
      FormEntity::DISPLAY_TYPE_POPUP => 0,
      FormEntity::DISPLAY_TYPE_SLIDE_IN => 0,
      FormEntity::DISPLAY_TYPE_OTHERS => 0,
      'with_first_name' => 0,
      'with_last_name' => 0,
      'with_custom_fields' => 0,
      'min_custom_fields' => 0,
      'max_custom_fields' => 0,
      'average_custom_fields' => 0,
      'median_custom_fields' => 0,
    ];

    foreach ($forms as $form) {
      $settings = $form->getSettings();
      if (!is_array($settings) || !isset($settings['form_placement'])) continue;

      $hasAnyEnabled = false;
      $formPlacement = $settings['form_placement'];

      foreach (FormEntity::FORM_DISPLAY_TYPES as $type) {
        if (isset($formPlacement[$type]['enabled']) && $formPlacement[$type]['enabled'] === '1') {
          $counts[$type]++;
          $hasAnyEnabled = true;
        }
      }

      if (!$hasAnyEnabled) {
        $counts[FormEntity::DISPLAY_TYPE_OTHERS]++;
      }
    }

    $customFieldsCounts = [];
    foreach ($formBlocks as $blocks) {
      if (in_array('first_name', $blocks)) {
        $counts['with_first_name']++;
      }
      if (in_array('last_name', $blocks)) {
        $counts['with_last_name']++;
      }

      $customFieldsInForm = array_filter($blocks, function($block) {
        return strpos($block, 'custom_') === 0;
      });

      if (!empty($customFieldsInForm)) {
        $counts['with_custom_fields']++;
        $customFieldsCounts[] = count($customFieldsInForm);
      }
    }

    if (!empty($customFieldsCounts)) {
      $counts['min_custom_fields'] = min($customFieldsCounts);
      $counts['max_custom_fields'] = max($customFieldsCounts);
      $counts['average_custom_fields'] = round(array_sum($customFieldsCounts) / count($customFieldsCounts), 1);
    }

    return $counts;
  }

  /**
   * Get active forms with their placements and blocks for analytics tracking
   * @return array
   */
  private function getActiveFormsBlocks(): array {
    $forms = $this->findAllActive();
    $formBlocks = [];

    foreach ($forms as $form) {
      $settings = $form->getSettings();
      if (!is_array($settings)) continue;

      $body = $form->getBody();
      if (!is_array($body)) continue;

      $formBlocks[] = $this->extractBlockTypes($body);
    }

    return $formBlocks;
  }

  /**
   * Extract block types from form body recursively
   * @param array $blocks
   * @return array
   */
  private function extractBlockTypes(array $blocks): array {
    $ignoredBlocks = ['columns', 'column', 'paragraph', 'heading', 'image', 'divider', 'submit', 'html'];
    $fieldBlockIds = ['email', 'first_name', 'last_name', 'segments'];
    $blockTypes = [];

    foreach ($blocks as $block) {
      if (in_array($block['id'], $fieldBlockIds)) {
        $blockTypes[] = $block['id'];
      } elseif (!in_array($block['type'], $ignoredBlocks)) {
        $blockTypes[] = 'custom_' . $block['type'];
      }

      // Recursively check nested blocks (like in columns)
      if (isset($block['body']) && is_array($block['body'])) {
        $blockTypes = array_merge($blockTypes, $this->extractBlockTypes($block['body']));
      }
    }

    return $blockTypes;
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

    $result = $this->entityManager->createQueryBuilder()
      ->update(FormEntity::class, 'f')
      ->set('f.deletedAt', 'CURRENT_TIMESTAMP()')
      ->where('f.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    // update was done via DQL, make sure the entities are also refreshed in the entity manager
    $this->refreshAll(function (FormEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });

    return $result;
  }

  public function bulkRestore(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $result = $this->entityManager->createQueryBuilder()
      ->update(FormEntity::class, 'f')
      ->set('f.deletedAt', ':deletedAt')
      ->where('f.id IN (:ids)')
      ->setParameter('deletedAt', null)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    // update was done via DQL, make sure the entities are also refreshed in the entity manager
    $this->refreshAll(function (FormEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });

    return $result;
  }

  public function bulkDelete(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $result = $this->entityManager->createQueryBuilder()
      ->delete(FormEntity::class, 'f')
      ->where('f.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (FormEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });

    return $result;
  }
}
