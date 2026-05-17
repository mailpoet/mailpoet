<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics\Export;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\Router\Router;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class StatisticsExporterTest extends \MailPoetUnitTest {
  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $previousTempUrl;

  /** @var string */
  private $tempDir;

  public function _before() {
    $this->previousTempPath = (string)Env::$tempPath;
    $this->previousTempUrl = (string)Env::$tempUrl;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-stats-export-' . uniqid('', true);
    mkdir($this->tempDir, 0777, true);
    Env::$tempPath = $this->tempDir;
    Env::$tempUrl = 'https://example.test/uploads/mailpoet';
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
    Env::$tempUrl = $this->previousTempUrl;
  }

  public function testItExportsAggregateAsCsvWithBomAndHeader() {
    $newsletter = $this->createNewsletter(123, 'Spring "sale"!', 'Spring, 2026', '2026-04-15 10:00:00');
    $stats = $this->createStats(500, 200, 30, 80, 5, 10, null);

    $exporter = $this->createExporter($stats);
    $result = $exporter->exportSingleAggregate($newsletter, StatisticsExporter::FORMAT_CSV);

    verify($result['totalExported'])->equals(1);
    $this->verifyStatisticsDownloadUrl($result['exportFileURL'], StatisticsExporter::FORMAT_CSV);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);
    verify($result['exportFileURL'])->stringNotContainsString(basename($files[0]));

    $content = (string)file_get_contents($files[0]);
    // Starts with the UTF-8 BOM (EF BB BF).
    verify(substr($content, 0, 3))->equals(chr(0xEF) . chr(0xBB) . chr(0xBF));

    $rows = $this->parseCsvRows(substr($content, 3));
    verify($rows)->arrayCount(2);
    verify($rows[0][0])->equals('Newsletter ID');
    verify($rows[0][1])->equals('Subject');
    verify($rows[0][4])->equals('Total sent');
    verify($rows[0][10])->equals('Revenue');
    verify($rows[1][0])->equals('123');
    verify($rows[1][1])->equals('Spring "sale"!');
    verify($rows[1][2])->equals('Spring, 2026');
    verify($rows[1][3])->equals('2026-04-15 10:00:00');
    verify($rows[1][4])->equals('500');
  }

  public function testItIncludesWooCommerceRevenueColumnsWhenPresent() {
    $newsletter = $this->createNewsletter(7, 'Black Friday', null, '2026-11-29 09:00:00');
    $revenue = new WooCommerceRevenue('USD', 1234.56, 12, $this->makeEmpty(Helper::class));
    $stats = $this->createStats(1000, 400, 50, 200, 10, 20, $revenue);

    $exporter = $this->createExporter($stats);
    $exporter->exportSingleAggregate($newsletter, StatisticsExporter::FORMAT_CSV);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    $rows = $this->parseCsvRows(substr((string)file_get_contents($files[0]), 3));
    verify($rows[1][10])->equals('1234.56');
    verify($rows[1][11])->equals('USD');
    verify($rows[1][12])->equals('12');
  }

  public function testItExportsAggregateAsXlsx() {
    $newsletter = $this->createNewsletter(42, 'Hello', null, '2026-01-01 00:00:00');
    $stats = $this->createStats(10, 5, 0, 1, 0, 0, null);

    $exporter = $this->createExporter($stats);
    $result = $exporter->exportSingleAggregate($newsletter, StatisticsExporter::FORMAT_XLSX);

    $this->verifyStatisticsDownloadUrl($result['exportFileURL'], StatisticsExporter::FORMAT_XLSX);
    $files = glob(ExportDownload::getExportDirectory() . '/*.xlsx') ?: [];
    verify($files)->arrayCount(1);
    verify(filesize($files[0]))->greaterThan(0);
  }

  public function testItRejectsUnsupportedFormat() {
    $newsletter = $this->createNewsletter(1, 'x', null, null);
    $stats = $this->createStats(0, 0, 0, 0, 0, 0, null);
    $exporter = $this->createExporter($stats);

    $this->expectException(\InvalidArgumentException::class);
    $exporter->exportSingleAggregate($newsletter, 'pdf');
  }

  public function testBuildAggregateRowMatchesHeaderColumnCount() {
    $newsletter = $this->createNewsletter(1, 's', null, null);
    $stats = $this->createStats(0, 0, 0, 0, 0, 0, null);
    $exporter = $this->createExporter($stats);

    $headers = $exporter->getAggregateHeaders();
    $row = $exporter->buildAggregateRow($newsletter, $stats);

    verify(count($row))->equals(count($headers));
  }

  public function testItExportsBulkAggregateAsCsv() {
    $newsletterA = $this->createNewsletter(1, 'A', null, '2026-01-01 00:00:00');
    $newsletterB = $this->createNewsletter(2, 'B', null, '2026-01-02 00:00:00');
    $stats = $this->createStats(10, 5, 0, 2, 0, 1, null);

    $exporter = $this->createExporter($stats);
    $result = $exporter->exportBulkAggregate([$newsletterA, $newsletterB], StatisticsExporter::FORMAT_CSV);

    verify($result['totalExported'])->equals(2);
    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);

    $body = substr((string)file_get_contents($files[0]), 3);
    $lines = explode("\n", trim($body));
    verify($lines)->arrayCount(3);
  }

  public function testItExportsRecipientsFromFilterRows() {
    $newsletter = $this->createNewsletter(99, 'Recipients', null, '2026-02-01 00:00:00');

    $rows = [
      [
        'subscriber_id' => 1,
        'email' => 'a@example.test',
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'status' => 'subscribed',
        'opened' => 'Y',
        'first_open_at' => '2026-02-02 12:00:00',
        'open_count' => 3,
        'machine_opened' => 'N',
        'clicked' => 'Y',
        'click_count' => 1,
        'bounced' => 'N',
        'unsubscribed' => 'N',
      ],
    ];

    $repository = $this->makeEmpty(NewsletterStatisticsRepository::class);
    $wp = $this->makeEmpty(WPFunctions::class, [
      'wpMkdirP' => function (string $dir) {
        return mkdir($dir, 0777, true);
      },
      'applyFilters' => function (string $hook, $value) use ($rows) {
        if ($hook === StatisticsExporter::FILTER_RECIPIENT_ROWS) {
          return $rows;
        }
        return $value;
      },
      'homeUrl' => 'https://example.test',
    ]);
    $exporter = new StatisticsExporter($repository, $wp);

    $result = $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_CSV);
    verify($result['totalExported'])->equals(1);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);
    $body = substr((string)file_get_contents($files[0]), 3);
    $exportedRows = $this->parseCsvRows($body);
    verify($exportedRows)->arrayCount(2);
    verify($exportedRows[1][1])->equals('a@example.test');
    verify($exportedRows[1][2])->equals('Alice');
  }

  public function testItExportsRecipientsAsEmptyFileWhenFilterReturnsNoRows() {
    $newsletter = $this->createNewsletter(99, 'Empty', null, '2026-02-01 00:00:00');
    $repository = $this->makeEmpty(NewsletterStatisticsRepository::class);
    $wp = $this->makeEmpty(WPFunctions::class, [
      'wpMkdirP' => function (string $dir) {
        return mkdir($dir, 0777, true);
      },
      'applyFilters' => function (string $hook, $value) {
        return $value;
      },
      'homeUrl' => 'https://example.test',
    ]);
    $exporter = new StatisticsExporter($repository, $wp);

    $result = $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_CSV);
    verify($result['totalExported'])->equals(0);
  }

  private function createExporter(NewsletterStatistics $stats): StatisticsExporter {
    $repository = $this->makeEmpty(NewsletterStatisticsRepository::class, [
      'getStatistics' => $stats,
    ]);
    $wp = $this->makeEmpty(WPFunctions::class, [
      'wpMkdirP' => function (string $dir) {
        return mkdir($dir, 0777, true);
      },
      'homeUrl' => 'https://example.test',
    ]);
    return new StatisticsExporter($repository, $wp);
  }

  private function verifyStatisticsDownloadUrl(string $url, string $extension): void {
    parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
    verify($query[Router::NAME] ?? null)->equals('');
    verify($query['endpoint'] ?? null)->equals(ExportDownload::ENDPOINT);
    verify($query['action'] ?? null)->equals('statistics_export');
    $data = Router::decodeRequestData($query['data'] ?? '');
    verify($data['token'] ?? null)->stringMatchesRegExp('/^[a-z0-9]{32}$/');
    verify($data['format'] ?? null)->equals($extension);
    verify(isset($data['filename']))->false();
  }

  private function getExportFiles(): array {
    return array_merge(
      glob(ExportDownload::getExportDirectory() . '/*') ?: [],
      glob(ExportDownload::getExportDirectory() . '/.htaccess') ?: []
    );
  }

  /**
   * @return array<array<string|null>>
   */
  private function parseCsvRows(string $body): array {
    return array_map(
      static function (string $line): array {
        return str_getcsv($line, ',', '"', '');
      },
      explode("\n", trim($body))
    );
  }

  private function createNewsletter(int $id, string $subject, ?string $campaignName, ?string $sentAt): NewsletterEntity {
    $newsletter = $this->makeEmpty(NewsletterEntity::class, [
      'getId' => $id,
      'getSubject' => $subject,
      'getCampaignName' => $campaignName,
      'getSentAt' => $sentAt ? new \DateTimeImmutable($sentAt) : null,
    ]);
    return $newsletter;
  }

  private function createStats(
    int $totalSent,
    int $opens,
    int $machineOpens,
    int $clicks,
    int $bounces,
    int $unsubscribes,
    ?WooCommerceRevenue $revenue
  ): NewsletterStatistics {
    $stats = new NewsletterStatistics($clicks, $opens, $unsubscribes, $bounces, $totalSent, $revenue);
    $stats->setMachineOpenCount($machineOpens);
    return $stats;
  }
}
