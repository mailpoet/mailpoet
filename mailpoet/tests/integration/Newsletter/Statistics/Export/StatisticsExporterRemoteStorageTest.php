<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics\Export;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Test\Router\Endpoints\RemoteStorageStreamWrapper;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/../../../Router/Endpoints/RemoteStorageStreamWrapper.php';

class StatisticsExporterRemoteStorageTest extends \MailPoetTest {
  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $previousTempUrl;

  public function _before() {
    parent::_before();
    RemoteStorageStreamWrapper::register();
    $this->previousTempPath = (string)Env::$tempPath;
    $this->previousTempUrl = (string)Env::$tempUrl;
    Env::$tempPath = RemoteStorageStreamWrapper::getRoot() . '/mailpoet';
    Env::$tempUrl = 'https://example.test/uploads/mailpoet';
  }

  public function _after() {
    Env::$tempPath = $this->previousTempPath;
    Env::$tempUrl = $this->previousTempUrl;
    RemoteStorageStreamWrapper::unregister();
    parent::_after();
  }

  public function testItExportsCsvToRemoteStorage() {
    $exception = $this->exportCsv();

    verify($exception)->null();
    verify(RemoteStorageStreamWrapper::$files)->arrayCount(1);
  }

  public function testItFailsWhenRemoteStorageCannotSaveTheCsvFile() {
    RemoteStorageStreamWrapper::$failSaves = true;

    $exception = $this->exportCsv();

    verify($exception)->instanceOf(\RuntimeException::class);
    verify($exception ? $exception->getMessage() : '')->equals('The export file could not be saved on the server.');
  }

  private function exportCsv(): ?\RuntimeException {
    $stats = new NewsletterStatistics(0, 0, 0, 0, 10, null);
    $stats->setMachineOpenCount(0);
    $exporter = new StatisticsExporter(
      $this->makeEmpty(NewsletterStatisticsRepository::class, ['getStatistics' => $stats]),
      $this->diContainer->get(TimeZoneCampaignScheduler::class),
      $this->diContainer->get(WPFunctions::class)
    );
    $newsletter = $this->makeEmpty(NewsletterEntity::class, ['getId' => 1, 'getSubject' => 'Subject']);
    $previousErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_USER_WARNING);
    try {
      $exporter->exportSingleAggregate($newsletter, StatisticsExporter::FORMAT_CSV);
    } catch (\RuntimeException $e) {
      return $e;
    } finally {
      error_reporting($previousErrorReporting);
    }
    return null;
  }
}
