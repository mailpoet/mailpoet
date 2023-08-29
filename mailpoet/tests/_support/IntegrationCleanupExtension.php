<?php declare(strict_types = 1);

namespace MailPoet\TestsSupport;

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SettingEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class IntegrationCleanupExtension extends Extension {
  /** @var EntityManager */
  private $entityManager;

  public static $events = [
    Events::TEST_BEFORE => 'beforeTest',
    Events::SUITE_BEFORE => 'beforeSuite',
  ];

  /** @var string */
  private $cleanupStatements;

  public function beforeSuite(SuiteEvent $event) {
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);

    $mpPrefix = Env::$dbPrefix;

    /** @var string[] $tables */
    $tables = $this->entityManager->getConnection()->fetchFirstColumn("
      SELECT table_name
      FROM information_schema.tables
      WHERE table_name LIKE '{$mpPrefix}%'
      AND table_name != '{$mpPrefix}migrations'
    ");

    $this->cleanupStatements = 'SET FOREIGN_KEY_CHECKS=0;';
    foreach ($tables as $table) {
      $this->cleanupStatements .= "DELETE FROM $table;";
    }

    // save plugin version to avoid triggering migrator and populator
    $settingsTable = $this->entityManager->getMetadataFactory()->getMetadataFor(SettingEntity::class)->getTableName();
    $dbVersion = Env::$version;
    $this->cleanupStatements .= "
      INSERT INTO $settingsTable (name, value, created_at, updated_at)
      VALUES ('db_version', '$dbVersion', NOW(), NOW());
    ";
    $this->cleanupStatements .= 'SET FOREIGN_KEY_CHECKS=1';
  }

  public function beforeTest(TestEvent $event) {
    $this->entityManager->getConnection()->executeStatement($this->cleanupStatements);
  }
}
