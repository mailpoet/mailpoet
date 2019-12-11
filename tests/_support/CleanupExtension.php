<?php

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class CleanupExtension extends Extension { // phpcs:ignore PSR1.Classes.ClassDeclaration
  const DB_BACKUP_PATH = __DIR__ . '/../_data/acceptanceBackup.sql';
  const DB_HOST = 'mysql';
  const DB_USERNAME = 'root';
  const DB_PASSWORD = 'wordpress';
  const DB_NAME = 'wordpress';
  const MAILHOG_DATA_PATH = '/mailhog-data';

  static $events = [
    Events::SUITE_BEFORE => 'backupDatabase',
    Events::TEST_BEFORE => 'cleanupEnvironment',
  ];

  /** @var PDO */
  private $rootConnection;

  public function __construct($config, $options) {
    parent::__construct($config, $options);
    $this->rootConnection = new PDO($this->createDsnConnectionString(), self::DB_USERNAME, self::DB_PASSWORD);
    $this->rootConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public function backupDatabase(SuiteEvent $event) {
    exec($this->createMysqlDumpCommand());
    $sql = file_get_contents(self::DB_BACKUP_PATH);

    // wrap dump with SQL preserving user session ($I->login() reuses session snapshot for performance)
    $sql = "
      SELECT meta_value
      INTO @mp_meta_value
      FROM mp_usermeta
      WHERE user_id = 1
      AND meta_key = 'session_tokens';
      $sql
      DELETE FROM mp_usermeta WHERE meta_key = 'session_tokens';
      INSERT INTO mp_usermeta (user_id, meta_key, meta_value) VALUES (1, 'session_tokens', @mp_meta_value);
    ";

    // set current plugin version to prevent executing Changelog.php setup for every test
    $version = Env::$version;
    $sql .= "
      \n\n
      INSERT INTO mp_mailpoet_settings (name, value) VALUES ('version', '$version')
      ON DUPLICATE KEY UPDATE value = '$version';
    ";

    // wrap SQL with serializable transaction (to avoid other connections like WP-CLI seeing wrong state)
    $sql = "
      SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;
      START TRANSACTION;
      $sql
      COMMIT;
    ";

    file_put_contents(self::DB_BACKUP_PATH, $sql);
  }

  public function cleanupEnvironment(TestEvent $event) {
    $this->rootConnection->exec(file_get_contents(self::DB_BACKUP_PATH));
    exec('rm -rf ' . self::MAILHOG_DATA_PATH . '/*', $output);

    // ensure Premium plugin is not installed
    $premium_plugin_path = ABSPATH . 'wp-content/plugins/mailpoet-premium';
    if (file_exists($premium_plugin_path)) {
      exec("rm -rf $premium_plugin_path", $output);
    }

    // cleanup EntityManager for data factories that are using it
    ContainerWrapper::getInstance()->get(EntityManager::class)->clear();
  }

  private function createDsnConnectionString() {
    return sprintf('mysql:host=%s;dbname=%s', self::DB_HOST, self::DB_NAME);
  }

  private function createMysqlDumpCommand() {
    return sprintf(
      'mysqldump --host=%s --user=%s --password=%s %s > %s',
      escapeshellarg(self::DB_HOST),
      escapeshellarg(self::DB_USERNAME),
      escapeshellarg(self::DB_PASSWORD),
      escapeshellarg(self::DB_NAME),
      escapeshellarg(self::DB_BACKUP_PATH)
    );
  }
}
