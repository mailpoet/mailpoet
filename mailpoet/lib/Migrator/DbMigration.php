<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Exception;
use MailPoetVendor\Doctrine\ORM\EntityManager;

abstract class DbMigration {
  /** @var Connection */
  protected $connection;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    ContainerWrapper $container
  ) {
    $this->connection = $container->get(Connection::class);
    $this->entityManager = $container->get(EntityManager::class);
  }

  abstract public function run(): void;

  /**
   * @param class-string<object> $entityClass
   */
  protected function getTableName(string $entityClass): string {
    return $this->entityManager->getClassMetadata($entityClass)->getTableName();
  }

  protected function createTable(string $tableName, array $attributes): void {
    $prefix = Env::$dbPrefix;
    $charsetCollate = Env::$dbCharsetCollate;
    $sql = implode(",\n", $attributes);
    $this->connection->executeStatement("
      CREATE TABLE IF NOT EXISTS {$prefix}{$tableName} (
        $sql
      ) {$charsetCollate};
    ");
  }

  protected function columnExists(string $tableName, string $columnName): bool {
    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      $this->connection->executeStatement("SELECT $columnName FROM $tableName LIMIT 0");
      return true;
    } catch (Exception $e) {
      return false;
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
  }

  protected function tableExists(string $tableName): bool {
    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      $this->connection->executeStatement("SELECT 1 FROM $tableName LIMIT 0");
      return true;
    } catch (Exception $e) {
      return false;
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
  }

  protected function indexExists(string $tableName, string $indexName): bool {
    global $wpdb;
    $suppressErrors = $wpdb->suppress_errors();
    try {
      $this->connection->executeStatement("ALTER TABLE $tableName ADD INDEX $indexName (__non__existent__column__name__)");
    } catch (Exception $e) {
      // Index creating index failed on not existing column we use a fallback. This can happen on MySQL 5.7 and lower and on some MariaDB versions.
      if ($e->getCode() === 1072) {
        $database = $wpdb->dbname;
        $result = $wpdb->get_var($wpdb->prepare(
          "SELECT count(*)
         FROM information_schema.statistics
         WHERE table_schema = COALESCE(DATABASE(), %s)
         AND table_name = %s
         AND index_name = %s",
          $database,
          $tableName,
          $indexName
        ));

        return $result > 0;
      }
      // Index exists when the error message contains its name. Otherwise, it's the non-existent column error.
      return strpos($e->getMessage(), $indexName) !== false;
    } finally {
      $wpdb->suppress_errors($suppressErrors);
    }
    return false;
  }
}
