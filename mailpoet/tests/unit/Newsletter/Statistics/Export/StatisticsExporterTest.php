<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics\Export;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
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
    $exporter = new StatisticsExporter($repository, $this->make(TimeZoneCampaignScheduler::class), $wp);

    $result = $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_CSV);
    verify($result['totalExported'])->equals(1);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);
    $body = substr((string)file_get_contents($files[0]), 3);
    $exportedRows = $this->parseCsvRows($body);
    verify($exportedRows)->arrayCount(2);
    verify($exportedRows[0])->arrayCount(13);
    verify($exportedRows[1])->arrayCount(13);
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
    $exporter = new StatisticsExporter($repository, $this->make(TimeZoneCampaignScheduler::class), $wp);

    $result = $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_CSV);
    verify($result['totalExported'])->equals(0);
  }

  public function testRecipientHeadersStayUnchangedForNonTimeZoneCampaigns() {
    $stats = $this->createStats(0, 0, 0, 0, 0, 0, null);
    $exporter = $this->createExporter($stats);

    $expectedHeaders = [
      'Subscriber ID',
      'Email',
      'First name',
      'Last name',
      'Status',
      'Opened',
      'First open at',
      'Open count',
      'Machine opened',
      'Clicked',
      'Click count',
      'Bounced',
      'Unsubscribed',
    ];

    verify($exporter->getRecipientHeaders())->equals($expectedHeaders);

    $regularQueue = new SendingQueueEntity();
    $newsletter = $this->createNewsletter(5, 'Regular', null, null, $regularQueue);
    verify($exporter->getRecipientHeaders($newsletter))->equals($expectedHeaders);
  }

  public function testItAppendsTimezoneHeadersForTimeZoneCampaigns() {
    $stats = $this->createStats(0, 0, 0, 0, 0, 0, null);
    $exporter = $this->createExporter($stats);
    $newsletter = $this->createNewsletter(6, 'Timezone', null, null, $this->createTimeZoneQueue());

    $headers = $exporter->getRecipientHeaders($newsletter);

    verify($headers)->arrayCount(16);
    verify(array_slice($headers, 0, 13))->equals($exporter->getRecipientHeaders());
    verify(array_slice($headers, 13))->equals([
      'Delivery timezone',
      'Timezone fallback used',
      'Local send time',
    ]);
  }

  public function testItExportsTimezoneRecipientCsvWithAlignedColumns() {
    $newsletter = $this->createNewsletter(77, 'Timezone recipients', null, '2026-02-01 00:00:00', $this->createTimeZoneQueue());

    $rows = [
      [1, 'a@example.test', 'Alice', 'Smith', 'subscribed', 'Y', '', 0, 'N', 'N', 0, 'N', 'N', 'Europe/Prague', 'No', '2026-02-01 01:00:00'],
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
    $exporter = new StatisticsExporter($repository, $this->make(TimeZoneCampaignScheduler::class), $wp);

    $result = $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_CSV);
    verify($result['totalExported'])->equals(1);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);
    $exportedRows = $this->parseCsvRows(substr((string)file_get_contents($files[0]), 3));
    verify($exportedRows)->arrayCount(2);
    verify($exportedRows[0])->arrayCount(16);
    verify($exportedRows[0][13])->equals('Delivery timezone');
    verify($exportedRows[0][14])->equals('Timezone fallback used');
    verify($exportedRows[0][15])->equals('Local send time');
    verify($exportedRows[1])->arrayCount(16);
    verify($exportedRows[1][13])->equals('Europe/Prague');
    verify($exportedRows[1][15])->equals('2026-02-01 01:00:00');
  }

  public function testItPadsLegacyRecipientFilterRowsForTimeZoneCampaigns() {
    $newsletter = $this->createNewsletter(79, 'Legacy timezone rows', null, '2026-02-01 00:00:00', $this->createTimeZoneQueue());
    $legacyRows = [
      [1, 'legacy@example.test', 'Legacy', 'Subscriber', 'subscribed', 'Y', '', 0, 'N', 'N', 0, 'N', 'N'],
    ];

    $repository = $this->makeEmpty(NewsletterStatisticsRepository::class);
    $wp = $this->makeEmpty(WPFunctions::class, [
      'wpMkdirP' => function (string $dir) {
        return mkdir($dir, 0777, true);
      },
      'applyFilters' => function (string $hook, $value) use ($legacyRows) {
        if ($hook === StatisticsExporter::FILTER_RECIPIENT_ROWS) {
          return $legacyRows;
        }
        return $value;
      },
      'homeUrl' => 'https://example.test',
    ]);
    $exporter = new StatisticsExporter($repository, $this->make(TimeZoneCampaignScheduler::class), $wp);

    $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_CSV);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);
    $exportedRows = $this->parseCsvRows(substr((string)file_get_contents($files[0]), 3));
    verify($exportedRows[0])->arrayCount(16);
    verify($exportedRows[1])->arrayCount(16);
    verify(array_slice($exportedRows[1], 13))->equals(['', '', '']);
  }

  public function testItExportsTimezoneRecipientsAsXlsx() {
    $newsletter = $this->createNewsletter(78, 'Timezone xlsx', null, '2026-02-01 00:00:00', $this->createTimeZoneQueue());
    $repository = $this->makeEmpty(NewsletterStatisticsRepository::class);
    $wp = $this->makeEmpty(WPFunctions::class, [
      'wpMkdirP' => function (string $dir) {
        return mkdir($dir, 0777, true);
      },
      'applyFilters' => function (string $hook, $value) {
        if ($hook === StatisticsExporter::FILTER_RECIPIENT_ROWS) {
          return [
            [1, 'a@example.test', 'Alice', 'Smith', 'subscribed', 'Y', '', 0, 'N', 'N', 0, 'N', 'N', 'Europe/Prague', 'No', '2026-02-01 01:00:00'],
          ];
        }
        return $value;
      },
      'homeUrl' => 'https://example.test',
    ]);
    $exporter = new StatisticsExporter($repository, $this->make(TimeZoneCampaignScheduler::class), $wp);

    $result = $exporter->exportRecipients($newsletter, StatisticsExporter::FORMAT_XLSX);

    verify($result['totalExported'])->equals(1);
    $files = glob(ExportDownload::getExportDirectory() . '/*.xlsx') ?: [];
    verify($files)->arrayCount(1);
    $exportedRows = $this->parseXlsxRows($files[0]);
    verify($exportedRows)->arrayCount(2);
    verify($exportedRows[0])->arrayCount(16);
    verify(array_slice($exportedRows[0], 13))->equals([
      'Delivery timezone',
      'Timezone fallback used',
      'Local send time',
    ]);
    verify($exportedRows[1])->arrayCount(16);
    verify(array_slice($exportedRows[1], 13))->equals([
      'Europe/Prague',
      'No',
      '2026-02-01 01:00:00',
    ]);
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
    return new StatisticsExporter($repository, $this->make(TimeZoneCampaignScheduler::class), $wp);
  }

  private function createTimeZoneQueue(): SendingQueueEntity {
    $queue = new SendingQueueEntity();
    $queue->setMeta([
      TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE => true,
      TimeZoneCampaignScheduler::META_TIMEZONE_CAMPAIGN_ID => 'campaign1234567890',
      TimeZoneCampaignScheduler::META_GROUP_TIMEZONE => 'Europe/Prague',
      TimeZoneCampaignScheduler::META_FALLBACK_USED => false,
    ]);
    return $queue;
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
   * @return array<array<string>>
   */
  private function parseXlsxRows(string $file): array {
    $archive = new \ZipArchive();
    if ($archive->open($file) !== true) {
      throw new \RuntimeException('Unable to open exported XLSX file.');
    }
    try {
      $sharedStringsXml = $archive->getFromName('xl/sharedStrings.xml');
      $worksheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
    } finally {
      $archive->close();
    }
    if (!is_string($sharedStringsXml) || !is_string($worksheetXml)) {
      throw new \RuntimeException('Exported XLSX file is missing worksheet data.');
    }

    $sharedStringsDocument = new \DOMDocument();
    $worksheetDocument = new \DOMDocument();
    if (!$sharedStringsDocument->loadXML($sharedStringsXml) || !$worksheetDocument->loadXML($worksheetXml)) {
      throw new \RuntimeException('Exported XLSX file contains invalid XML.');
    }

    $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $sharedStrings = [];
    foreach ($sharedStringsDocument->getElementsByTagNameNS($namespace, 'si') as $sharedStringNode) {
      if (!$sharedStringNode instanceof \DOMElement) {
        throw new \RuntimeException('Unable to read exported XLSX shared string node.');
      }
      $sharedStrings[] = $sharedStringNode->textContent;
    }

    $rows = [];
    foreach ($worksheetDocument->getElementsByTagNameNS($namespace, 'row') as $rowNode) {
      if (!$rowNode instanceof \DOMElement) {
        throw new \RuntimeException('Unable to read exported XLSX row node.');
      }
      $row = [];
      foreach ($rowNode->childNodes as $cellNode) {
        if (!$cellNode instanceof \DOMElement || $cellNode->localName !== 'c') {
          continue;
        }
        $valueNode = $cellNode->getElementsByTagNameNS($namespace, 'v')->item(0);
        if ($valueNode !== null && !$valueNode instanceof \DOMElement) {
          throw new \RuntimeException('Unable to read exported XLSX cell value node.');
        }
        $value = $valueNode ? $valueNode->textContent : '';
        $row[] = $cellNode->getAttribute('t') === 's'
          ? ($sharedStrings[(int)$value] ?? '')
          : $value;
      }
      $rows[] = $row;
    }
    return $rows;
  }

  public function testItGuardsCsvValuesASpreadsheetWouldEvaluate() {
    $newsletter = $this->createNewsletter(123, '=SUM(1+1)', '@campaign', '2026-04-15 10:00:00');
    $revenue = new WooCommerceRevenue('USD', -12.5, 3, $this->makeEmpty(Helper::class));
    $stats = $this->createStats(500, 200, 30, 80, 5, 10, $revenue);

    $exporter = $this->createExporter($stats);
    $exporter->exportSingleAggregate($newsletter, StatisticsExporter::FORMAT_CSV);

    $files = glob(ExportDownload::getExportDirectory() . '/*.csv') ?: [];
    verify($files)->arrayCount(1);
    $rows = $this->parseCsvRows(substr((string)file_get_contents($files[0]), 3));

    verify($rows[1][1])->equals("'=SUM(1+1)");
    verify($rows[1][2])->equals("'@campaign");
    // Figures MailPoet computed itself stay numbers, including a negative one, which
    // would otherwise be prefixed because it starts with a minus.
    verify($rows[1][4])->equals('500');
    verify($rows[1][10])->equals('-12.5');
  }

  public function testItDoesNotStoreExportedTextAsAnXlsxFormula() {
    $newsletter = $this->createNewsletter(42, '=SUM(1+1)', null, '2026-01-01 00:00:00');
    $revenue = new WooCommerceRevenue('USD', -12.5, 3, $this->makeEmpty(Helper::class));
    $stats = $this->createStats(10, 5, 0, 1, 0, 0, $revenue);

    $exporter = $this->createExporter($stats);
    $exporter->exportSingleAggregate($newsletter, StatisticsExporter::FORMAT_XLSX);

    $files = glob(ExportDownload::getExportDirectory() . '/*.xlsx') ?: [];
    verify($files)->arrayCount(1);

    $worksheet = $this->readXlsxWorksheet($files[0]);
    verify($worksheet)->stringNotContainsString('<f>');

    $rows = $this->parseXlsxRows($files[0]);
    // Stored as plain text, so the reader shows the value itself rather than a guard character.
    verify($rows[1][1])->equals('=SUM(1+1)');
    verify($rows[0][1])->equals('Subject');
    // A negative figure MailPoet computed stays a number instead of becoming text.
    verify($rows[1][10])->equals('-12.5');
    verify($worksheet)->stringNotContainsString("'-12.5");
  }

  private function readXlsxWorksheet(string $path): string {
    $archive = new \ZipArchive();
    verify($archive->open($path))->true();
    $worksheet = (string)$archive->getFromName('xl/worksheets/sheet1.xml');
    $archive->close();
    return $worksheet;
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

  private function createNewsletter(int $id, string $subject, ?string $campaignName, ?string $sentAt, ?SendingQueueEntity $queue = null): NewsletterEntity {
    $newsletter = $this->makeEmpty(NewsletterEntity::class, [
      'getId' => $id,
      'getSubject' => $subject,
      'getCampaignName' => $campaignName,
      'getSentAt' => $sentAt ? new \DateTimeImmutable($sentAt) : null,
      'getLatestQueue' => $queue,
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
