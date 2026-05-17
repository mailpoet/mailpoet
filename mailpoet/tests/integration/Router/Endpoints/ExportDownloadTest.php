<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use MailPoet\Config\Env;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\Router\Router;
use MailPoet\Subscribers\ImportExport\Export\Export;
use MailPoet\WP\Functions as WPFunctions;

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
