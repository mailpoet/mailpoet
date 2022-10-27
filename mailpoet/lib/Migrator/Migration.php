<?php declare(strict_types = 1);

namespace MailPoet\Migrator;

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
}
