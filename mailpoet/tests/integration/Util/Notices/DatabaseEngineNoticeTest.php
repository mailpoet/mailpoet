<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

class DatabaseEngineNoticeTest extends \MailPoetTest {
  /** @var DatabaseEngineNotice */
  private $notice;

  private $tableName;

  public function _before() {
    parent::_before();
    $this->tableName = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $this->notice = new DatabaseEngineNotice(
      new WPFunctions(),
      $this->entityManager
    );
  }

  public function _after() {
    $this->entityManager->getConnection()->executeStatement("
      ALTER TABLE {$this->tableName}
      ENGINE = INNODB;
    ");
    parent::_after();
  }

  public function testItDisplaysNoticeWhenMyISAMDetected() {
    $this->entityManager->getConnection()->executeStatement("
      ALTER TABLE {$this->tableName}
      ENGINE = MyISAM;
    ");
    $result = $this->notice->init(true);
    verify($result)->notEmpty();
    $message = $result->getMessage();
    verify($message)->stringContainsString('Some of the MailPoet plugin’s tables are not using the InnoDB engine');
    verify($message)->stringContainsString('https://kb.mailpoet.com/article/200-solving-database-connection-issues#database-configuration');
    verify($message)->stringContainsString($this->tableName);
  }

  public function testItDisplaysNoticeWithMultipleTables() {
    $tables = [
      $this->entityManager->getClassMetadata(FormEntity::class)->getTableName(),
      $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName(),
      $this->tableName,
      $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName(),
    ];
    foreach ($tables as $table) {
      $this->entityManager->getConnection()->executeStatement("ALTER TABLE {$table} ENGINE = MyISAM;");
    }
    $result = $this->notice->init(true);
    verify($result)->notEmpty();
    $message = $result->getMessage();
    verify($message)->stringContainsString('and 2 more');
    verify($message)->stringContainsString('“' . $tables[0] . '”');

    foreach ($tables as $table) {
      $this->entityManager->getConnection()->executeStatement("ALTER TABLE {$table} ENGINE = INNODB;");
    }
  }

  public function testItDoesntDisplayWhenDisabled() {
    $this->notice->disable();
    $result = $this->notice->init(true);
    verify($result)->null();
  }
}
