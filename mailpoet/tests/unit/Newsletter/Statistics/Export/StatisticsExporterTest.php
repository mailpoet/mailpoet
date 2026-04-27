<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics\Export;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
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
    foreach (glob($this->tempDir . '/*') ?: [] as $file) {
      unlink($file);
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
    verify($result['exportFileURL'])->stringStartsWith('https://example.test/uploads/mailpoet/MailPoet_stats_export_');
    verify($result['exportFileURL'])->stringEndsWith('.csv');

    $files = glob($this->tempDir . '/*.csv') ?: [];
    verify($files)->arrayCount(1);

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

    $files = glob($this->tempDir . '/*.csv') ?: [];
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

    verify($result['exportFileURL'])->stringEndsWith('.xlsx');
    $files = glob($this->tempDir . '/*.xlsx') ?: [];
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

  private function createExporter(NewsletterStatistics $stats): StatisticsExporter {
    $repository = $this->makeEmpty(NewsletterStatisticsRepository::class, [
      'getStatistics' => $stats,
    ]);
    $wp = $this->makeEmpty(WPFunctions::class, [
      'wpMkdirP' => true,
    ]);
    return new StatisticsExporter($repository, $wp);
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
