<?php

namespace MailPoet\Util;

use MailPoetVendor\Doctrine\ORM\EntityManager;

class DBCollationChecker {

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  /**
   * If two columns have a different collations returns MySQL's COLLATE command to be used with the target table column.
   * e.g. WHERE source_table.column = target_table.column COLLATE xyz
   */
  public function getCollateIfNeeded(string $sourceTable, string $sourceColumn, string $targetTable, string $targetColumn): string {
    $connection = $this->entityManager->getConnection();
    $sourceColumnData = $connection->executeQuery("SHOW FULL COLUMNS FROM $sourceTable WHERE Field = '$sourceColumn';")->fetchAllAssociative();
    $sourceCollation = $sourceColumnData[0]['Collation'] ?? '';
    $targetColumnData = $connection->executeQuery("SHOW FULL COLUMNS FROM $targetTable WHERE Field = '$targetColumn';")->fetchAllAssociative();
    $targetCollation = $targetColumnData[0]['Collation'] ?? '';
    if ($sourceCollation !== $targetCollation) {
      return "COLLATE $sourceCollation";
    }
    return '';
  }
}
