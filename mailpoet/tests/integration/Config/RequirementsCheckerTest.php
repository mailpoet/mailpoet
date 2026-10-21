<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoet\Config\RequirementsChecker;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\Test\Router\Endpoints\RemoteStorageStreamWrapper;

require_once __DIR__ . '/../Router/Endpoints/RemoteStorageStreamWrapper.php';

class RequirementsCheckerTest extends \MailPoetTest {
  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $tempDir;

  public function _before() {
    parent::_before();
    $this->previousTempPath = (string)Env::$tempPath;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-requirements-' . uniqid('', true);
    Env::$tempPath = $this->tempDir;
  }

  public function _after() {
    RemoteStorageStreamWrapper::unregister();
    if (is_file($this->tempDir . '/index.php')) {
      unlink($this->tempDir . '/index.php');
    }
    if (is_dir($this->tempDir)) {
      rmdir($this->tempDir);
    }
    Env::$tempPath = $this->previousTempPath;
    parent::_after();
  }

  public function testItCreatesTempFolderWithoutPreparingExportDirectory() {
    $checker = new RequirementsChecker(false);

    verify($checker->checkTempFolderCreation())->true();

    verify(is_file($this->tempDir . '/index.php'))->true();
    verify(is_dir(ExportDownload::getExportDirectory()))->false();
  }

  public function testItWritesTheIndexFileWhenUploadsUseTheFileScheme() {
    Env::$tempPath = 'file://' . $this->tempDir;
    $checker = new RequirementsChecker(false);

    verify($checker->checkTempFolderCreation())->true();

    verify(is_file($this->tempDir . '/index.php'))->true();
  }

  public function testItWritesNothingWhenUploadsAreOnRemoteStorage() {
    RemoteStorageStreamWrapper::register();
    Env::$tempPath = RemoteStorageStreamWrapper::getRoot() . '/mailpoet';
    $checker = new RequirementsChecker(false);

    verify($checker->checkTempFolderCreation())->true();

    verify(RemoteStorageStreamWrapper::$writes)->arrayCount(0);
  }
}
