<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

abstract class Migration {
  /** @var ContainerWrapper */
  protected $container;

  /** @var Connection */
  protected $connection;

  /** @var EntityManager */
  protected $entityManager;

  public function __construct(
    ContainerWrapper $container
  ) {
    $this->container = $container;
    $this->connection = $container->get(Connection::class);
    $this->entityManager = $container->get(EntityManager::class);
  }

  abstract public function run(): void;

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
}
