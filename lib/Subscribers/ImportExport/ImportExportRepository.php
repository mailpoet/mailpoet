<?php

namespace MailPoet\Subscribers\ImportExport;

use DateTime;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadata;

class ImportExportRepository {
  private const IGNORED_COLUMNS_FOR_BULK_UPDATE = [
    SubscriberEntity::class => [
      'wp_user_id',
      'is_woocommerce_user',
      'email',
      'created_at',
      'last_subscribed_at',
    ],
    SubscriberCustomFieldEntity::class => [
      'created_at',
    ],
    SubscriberSegmentEntity::class => [
      'created_at',
    ],
  ];

  private const KEY_COLUMNS_FOR_BULK_UPDATE = [
    SubscriberEntity::class => [
      'email',
    ],
    SubscriberCustomFieldEntity::class => [
      'subscriber_id',
      'custom_field_id',
    ],
  ];

  /** @var EntityManager */
  protected $entityManager;

  /** @var string[] */
  protected $ignoreColumnsForBulkUpdate = [
    'created_at',
  ];

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  protected function getClassMetadata(string $className): ClassMetadata {
    return $this->entityManager->getClassMetadata($className);
  }

  protected function getTableName(string $className): string {
    return $this->getClassMetadata($className)->getTableName();
  }

  protected function getTableColumns(string $className): array {
    return $this->getClassMetadata($className)->getColumnNames();
  }

  public function insertMultiple(
    string $className,
    array $columns,
    array $data
  ): int {
    $tableName = $this->getTableName($className);

    if (!$columns || !$data) {
      return 0;
    }

    $rows = [];
    $parameters = [];
    foreach ($data as $key => $item) {
      $paramNames = array_map(function (string $parameter) use ($key): string {
        return ":{$parameter}_{$key}";
      }, $columns);

      foreach ($item as $columnKey => $column) {
        $parameters[$paramNames[$columnKey]] = $column;
      }
      $rows[] = "(" . implode(', ', $paramNames) . ")";
    }

    return $this->entityManager->getConnection()->executeUpdate("
      INSERT IGNORE INTO {$tableName} (`" . implode("`, `", $columns) . "`) VALUES 
      " . implode(", \n", $rows) . "
    ", $parameters);
  }

  public function updateMultiple(
    string $className,
    array $columns,
    array $data,
    ?DateTime $updatedAt = null
  ): int {
    $tableName = $this->getTableName($className);
    $entityColumns = $this->getTableColumns($className);

    if (!$columns || !$data) {
      return 0;
    }

    $parameters = [];
    $parameterTypes = [];
    $keyColumns = self::KEY_COLUMNS_FOR_BULK_UPDATE[$className] ?? [];
    if (!$keyColumns) {
      return 0;
    }

    $keyColumnsConditions = [];
    foreach ($keyColumns as $keyColumn) {
      $columnIndex = array_search($keyColumn, $columns);
      $parameters[$keyColumn] = array_map(function(array $row) use ($columnIndex) {
        return $row[$columnIndex];
      }, $data);
      $parameterTypes[$keyColumn] = Connection::PARAM_STR_ARRAY;
      $keyColumnsConditions[] = "{$keyColumn} IN (:{$keyColumn})";
    }

    $ignoredColumns = self::IGNORED_COLUMNS_FOR_BULK_UPDATE[$className] ?? ['created_at'];
    $updateColumns = array_map(function($columnName) use ($keyColumns, $columns, $data, &$parameters): string {   
      $values = [];
      foreach ($data as $index => $row) {
        $keyCondition = array_map(function($keyColumn) use ($index, $row, $columns, &$parameters): string {
          $parameters["{$keyColumn}_{$index}"] = $row[array_search($keyColumn, $columns)];
          return "{$keyColumn} = :{$keyColumn}_{$index}";
        }, $keyColumns);
        $values[] = "WHEN " . implode(' AND ', $keyCondition) . " THEN :{$columnName}_{$index}";
        $parameters["{$columnName}_{$index}"] = $row[array_search($columnName, $columns)];
      }
      return "{$columnName} = (CASE " . implode("\n", $values) . " END)";
    }, array_diff($columns, $ignoredColumns));

    if ($updatedAt && in_array('updated_at', $entityColumns, true)) {
      $parameters['updated_at'] = $updatedAt;
      $updateColumns[] = "updated_at = :updated_at";
    }

    // we want to reset deleted_at for updated rows
    if (in_array('deleted_at', $entityColumns, true)) {
      $updateColumns[] = 'deleted_at = NULL';
    }

    return $this->entityManager->getConnection()->executeUpdate("
      UPDATE {$tableName} SET
      " . implode(", \n", $updateColumns) . "
      WHERE 
      " . implode(' AND ', $keyColumnsConditions) . "
    ", $parameters, $parameterTypes); 
  }
}
