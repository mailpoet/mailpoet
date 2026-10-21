<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Router\Endpoints\ExportDownload;

class ActivatorExportDirectoryTest extends \MailPoetTest {
  /** @var Activator */
  private $activator;

  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $tempDir;

  public function _before() {
    parent::_before();
    $this->activator = $this->diContainer->get(Activator::class);
    $this->previousTempPath = (string)Env::$tempPath;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-activator-' . uniqid('', true);
    mkdir($this->tempDir, 0777, true);
    Env::$tempPath = $this->tempDir;
  }

  public function _after() {
    $exportDirectory = ExportDownload::getExportDirectory();
    if (is_dir($exportDirectory)) {
      foreach (['index.php', '.htaccess'] as $file) {
        if (is_file($exportDirectory . '/' . $file)) {
          unlink($exportDirectory . '/' . $file);
        }
      }
      rmdir($exportDirectory);
    } elseif (is_file($exportDirectory)) {
      unlink($exportDirectory);
    }
    rmdir($this->tempDir);
    Env::$tempPath = $this->previousTempPath;
    parent::_after();
  }

  public function testActivationPreparesExportDirectory() {
    $this->activator->activate();

    verify(is_file(ExportDownload::getExportDirectory() . '/index.php'))->true();
    verify(is_file(ExportDownload::getExportDirectory() . '/.htaccess'))->true();
  }

  public function testActivationCompletesWhenExportDirectoryCannotBePrepared() {
    file_put_contents(ExportDownload::getExportDirectory(), 'not a directory');

    $this->activator->activate();

    verify(is_file(ExportDownload::getExportDirectory()))->true();
  }
}
