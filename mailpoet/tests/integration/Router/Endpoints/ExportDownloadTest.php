<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use MailPoet\Config\Env;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\Router\Router;
use MailPoet\Subscribers\ImportExport\Export\Export;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/RemoteStorageStreamWrapper.php';

class ExportDownloadTest extends \MailPoetTest {
  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $tempDir;

  public function _before() {
    parent::_before();
    $this->previousTempPath = (string)Env::$tempPath;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-export-download-' . uniqid('', true);
    mkdir($this->tempDir, 0777, true);
    Env::$tempPath = $this->tempDir;
    ExportDownload::ensureExportDirectory(new WPFunctions());
  }

  public function _after() {
    RemoteStorageStreamWrapper::unregister();
    Env::$tempPath = $this->tempDir;
    foreach ($this->getExportFiles() as $file) {
      unlink($file);
    }
    if (is_dir(ExportDownload::getExportDirectory())) {
      rmdir(ExportDownload::getExportDirectory());
    }
    if (is_dir($this->tempDir)) {
      rmdir($this->tempDir);
    }
    Env::$tempPath = $this->previousTempPath;
    parent::_after();
  }

  public function testItBuildsSubscriberExportDownloadUrl() {
    $url = ExportDownload::buildSubscriberExportUrl('abc123abc123abc123abc123abc123ab', 'csv');
    $data = $this->getRouterData($url);

    verify($data['endpoint'])->equals(ExportDownload::ENDPOINT);
    verify($data['action'])->equals('subscriber_export');
    verify($data['data']['token'])->equals('abc123abc123abc123abc123abc123ab');
    verify($data['data']['format'])->equals('csv');
    verify(isset($data['data']['filename']))->false();
  }

  public function testItBuildsStatisticsExportDownloadUrl() {
    $url = ExportDownload::buildStatisticsExportUrl('abc123abc123abc123abc123abc123ab', 'xlsx');
    $data = $this->getRouterData($url);

    verify($data['endpoint'])->equals(ExportDownload::ENDPOINT);
    verify($data['action'])->equals('statistics_export');
    verify($data['data']['token'])->equals('abc123abc123abc123abc123abc123ab');
    verify($data['data']['format'])->equals('xlsx');
    verify(isset($data['data']['filename']))->false();
  }

  public function testItAllowsValidExportFilesFromTempDirectory() {
    $file = ExportDownload::createExportFile(Export::getFilePrefix(), 'csv');
    file_put_contents($file['path'], 'email');
    $endpoint = new ExportDownload(new WPFunctions());

    verify($endpoint->getDownloadFilePath(
      [
        'token' => $file['token'],
        'format' => 'csv',
      ],
      Export::getFilePrefix()
    ))->equals(realpath($file['path']));
  }

  public function testItRejectsUnexpectedExportFileNames() {
    $file = ExportDownload::createExportFile(StatisticsExporter::FILE_PREFIX, 'csv');
    file_put_contents($file['path'], 'stats');
    $endpoint = new ExportDownload(new WPFunctions());

    verify($endpoint->getDownloadFilePath(
      [
        'token' => '../MailPoet_stats_export_abc123abc123abc.csv',
        'format' => 'csv',
      ],
      StatisticsExporter::FILE_PREFIX
    ))->null();
    verify($endpoint->getDownloadFilePath(
      [
        'token' => $file['token'],
        'format' => 'csv',
      ],
      Export::getFilePrefix()
    ))->null();
    verify($endpoint->getDownloadFilePath(
      [
        'token' => $file['token'],
        'format' => 'php',
      ],
      StatisticsExporter::FILE_PREFIX
    ))->null();
  }

  public function testItPurgesLegacyExportFilesWhenCreatingDirectory() {
    foreach ($this->getExportFiles() as $file) {
      unlink($file);
    }
    rmdir(ExportDownload::getExportDirectory());

    $legacySubscriberFile = $this->tempDir . '/' . Export::getFilePrefix() . 'legacy.csv';
    $legacyStatsFile = $this->tempDir . '/' . StatisticsExporter::FILE_PREFIX . 'legacy.csv';
    $unrelatedFile = $this->tempDir . '/unrelated.txt';
    file_put_contents($legacySubscriberFile, 'legacy');
    file_put_contents($legacyStatsFile, 'legacy');
    file_put_contents($unrelatedFile, 'keep');

    ExportDownload::ensureExportDirectory(new WPFunctions());

    $this->assertFileNotExists($legacySubscriberFile);
    $this->assertFileNotExists($legacyStatsFile);
    $this->assertFileExists($unrelatedFile);

    unlink($unrelatedFile);
  }

  public function testItDoesNotPurgeLegacyExportFilesWhenDirectoryAlreadyExists() {
    $legacySubscriberFile = $this->tempDir . '/' . Export::getFilePrefix() . 'legacy.csv';
    file_put_contents($legacySubscriberFile, 'legacy');

    ExportDownload::ensureExportDirectory(new WPFunctions());

    $this->assertFileExists($legacySubscriberFile);
    unlink($legacySubscriberFile);
  }

  public function testItFailsWhenExportDirectoryCannotBeCreated() {
    $previousTempPath = Env::$tempPath;
    Env::$tempPath = $this->tempDir . '/missing';
    $wp = new class extends WPFunctions {
      public function wpMkdirP(string $dir) {
        return false;
      }
    };

    try {
      ExportDownload::ensureExportDirectory($wp);
      $this->fail('Export directory creation did not fail');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('Could not create the export directory.');
    } finally {
      Env::$tempPath = $previousTempPath;
    }
  }

  public function testItWritesServerFilesOnLocalDisk() {
    $exportDirectory = ExportDownload::getExportDirectory();

    $this->assertFileExists($exportDirectory . '/index.php');
    $this->assertStringContainsString('Require all denied', (string)file_get_contents($exportDirectory . '/.htaccess'));
  }

  public function testItSkipsServerFilesWhenExportDirectoryIsOnRemoteStorage() {
    $this->useRemoteStorage();

    ExportDownload::ensureExportDirectory(new WPFunctions());

    verify(RemoteStorageStreamWrapper::$writes)->arrayCount(0);
  }

  public function testItWritesServerFilesOnRemoteStorageWhenFilterTurnsThemOn() {
    $this->useRemoteStorage();
    $wp = new WPFunctions();
    $wp->addFilter('mailpoet_export_directory_server_files', '__return_true');

    try {
      ExportDownload::ensureExportDirectory($wp);
    } finally {
      $wp->removeFilter('mailpoet_export_directory_server_files', '__return_true');
    }

    verify(RemoteStorageStreamWrapper::$files)->arrayHasKey(ExportDownload::getExportDirectory() . '/index.php');
    verify(RemoteStorageStreamWrapper::$files)->arrayHasKey(ExportDownload::getExportDirectory() . '/.htaccess');
  }

  public function testItSkipsServerFilesWhenFilterTurnsThemOff() {
    foreach ($this->getExportFiles() as $file) {
      unlink($file);
    }
    rmdir(ExportDownload::getExportDirectory());
    $wp = new WPFunctions();
    $wp->addFilter('mailpoet_export_directory_server_files', '__return_false');

    try {
      ExportDownload::ensureExportDirectory($wp);
    } finally {
      $wp->removeFilter('mailpoet_export_directory_server_files', '__return_false');
    }

    verify(is_dir(ExportDownload::getExportDirectory()))->true();
    $this->assertFileNotExists(ExportDownload::getExportDirectory() . '/index.php');
    $this->assertFileNotExists(ExportDownload::getExportDirectory() . '/.htaccess');
  }

  public function testItAllowsValidExportFilesOnRemoteStorage() {
    $this->useRemoteStorage();
    $file = ExportDownload::createExportFile(Export::getFilePrefix(), 'csv');
    file_put_contents($file['path'], 'email');
    $endpoint = new ExportDownload(new WPFunctions());

    verify($endpoint->getDownloadFilePath(
      [
        'token' => $file['token'],
        'format' => 'csv',
      ],
      Export::getFilePrefix()
    ))->equals($file['path']);
  }

  public function testItRejectsUnexpectedExportFileNamesOnRemoteStorage() {
    $this->useRemoteStorage();
    $file = ExportDownload::createExportFile(StatisticsExporter::FILE_PREFIX, 'csv');
    file_put_contents($file['path'], 'stats');
    $endpoint = new ExportDownload(new WPFunctions());

    verify($endpoint->getDownloadFilePath(
      [
        'token' => '../MailPoet_stats_export_abc123abc123abc.csv',
        'format' => 'csv',
      ],
      StatisticsExporter::FILE_PREFIX
    ))->null();
    verify($endpoint->getDownloadFilePath(
      [
        'token' => $file['token'],
        'format' => 'csv',
      ],
      Export::getFilePrefix()
    ))->null();
    verify($endpoint->getDownloadFilePath(
      [
        'token' => $file['token'],
        'format' => 'php',
      ],
      StatisticsExporter::FILE_PREFIX
    ))->null();
    verify($endpoint->getDownloadFilePath(
      [
        'token' => ExportDownload::generateDownloadToken(),
        'format' => 'csv',
      ],
      StatisticsExporter::FILE_PREFIX
    ))->null();
  }

  public function testItWritesServerFilesWhenUploadsUseTheFileScheme() {
    Env::$tempPath = 'file://' . $this->tempDir . '/file-scheme';

    try {
      ExportDownload::ensureExportDirectory(new WPFunctions());
      $this->assertFileExists($this->tempDir . '/file-scheme/exports/.htaccess');
      $this->assertFileExists($this->tempDir . '/file-scheme/exports/index.php');
    } finally {
      $this->removeFileSchemeDirectory();
    }
  }

  public function testItAllowsValidExportFilesWhenUploadsUseTheFileScheme() {
    Env::$tempPath = 'file://' . $this->tempDir . '/file-scheme';

    try {
      ExportDownload::ensureExportDirectory(new WPFunctions());
      $file = ExportDownload::createExportFile(Export::getFilePrefix(), 'csv');
      file_put_contents($file['path'], 'email');
      $endpoint = new ExportDownload(new WPFunctions());

      verify($endpoint->getDownloadFilePath(
        [
          'token' => $file['token'],
          'format' => 'csv',
        ],
        Export::getFilePrefix()
      ))->equals(realpath(substr($file['path'], strlen('file://'))));
    } finally {
      $this->removeFileSchemeDirectory();
    }
  }

  private function removeFileSchemeDirectory(): void {
    $directory = $this->tempDir . '/file-scheme';
    foreach (array_merge(glob($directory . '/exports/*') ?: [], [$directory . '/exports/.htaccess']) as $file) {
      if (is_file($file)) {
        unlink($file);
      }
    }
    if (is_dir($directory . '/exports')) {
      rmdir($directory . '/exports');
    }
    if (is_dir($directory)) {
      rmdir($directory);
    }
  }

  private function useRemoteStorage(): void {
    RemoteStorageStreamWrapper::register();
    Env::$tempPath = RemoteStorageStreamWrapper::getRoot() . '/mailpoet';
  }

  private function getRouterData(string $url): array {
    parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
    return [
      'endpoint' => $query['endpoint'] ?? null,
      'action' => $query['action'] ?? null,
      'data' => Router::decodeRequestData($query['data'] ?? ''),
    ];
  }

  private function getExportFiles(): array {
    return array_merge(
      glob(ExportDownload::getExportDirectory() . '/*') ?: [],
      glob(ExportDownload::getExportDirectory() . '/.htaccess') ?: []
    );
  }
}
