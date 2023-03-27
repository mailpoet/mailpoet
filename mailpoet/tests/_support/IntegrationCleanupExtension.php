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
  private $deleteStatement;

  public function beforeSuite(SuiteEvent $event) {
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);

    $this->deleteStatement = 'SET FOREIGN_KEY_CHECKS=0;';
    foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
      $class = $metadata->getName();
      $table = $metadata->getTableName();
      $this->deleteStatement .= "DELETE FROM $table;";

      // save plugin version to avoid triggering migrator and populator
      if ($class === SettingEntity::class) {
        $dbVersion = Env::$version;
        $this->deleteStatement .= "
          INSERT INTO $table (name, value, created_at, updated_at)
          VALUES ('db_version', '$dbVersion', NOW(), NOW());
        ";
      }
    }
    $this->deleteStatement .= 'SET FOREIGN_KEY_CHECKS=1';
  }

  public function beforeTest(TestEvent $event) {
    $this->entityManager->getConnection()->executeStatement($this->deleteStatement);
  }
}
