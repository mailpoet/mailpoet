<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics\Export;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Router\Endpoints\ExportDownload;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\XLSXWriter;

class StatisticsExporter {
  public const FORMAT_CSV = 'csv';
  public const FORMAT_XLSX = 'xlsx';

  public const FILE_PREFIX = 'MailPoet_stats_export_';
  /**
   * Filter applied to populate per-recipient rows when exporting recipients.
   * The free plugin ships an empty implementation; the premium plugin
   * registers a callback that returns the per-recipient data.
   *
   * Filter signature: (array $rows, NewsletterEntity $newsletter): array
   */
  public const FILTER_RECIPIENT_ROWS = 'mailpoet_statistics_export_recipient_rows';

  /** @var NewsletterStatisticsRepository */
  private $statisticsRepository;

  /** @var TimeZoneCampaignScheduler */
  private $timeZoneCampaignScheduler;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterStatisticsRepository $statisticsRepository,
    TimeZoneCampaignScheduler $timeZoneCampaignScheduler,
    WPFunctions $wp
  ) {
    $this->statisticsRepository = $statisticsRepository;
    $this->timeZoneCampaignScheduler = $timeZoneCampaignScheduler;
    $this->wp = $wp;
  }

  /**
   * Export aggregate stats for a single newsletter to CSV or XLSX.
   *
   * @return array{exportFileURL: string, totalExported: int}
   */
  public function exportSingleAggregate(NewsletterEntity $newsletter, string $format): array {
    $format = $this->normalizeFormat($format);
    $headers = $this->getAggregateHeaders();
    $stats = $this->statisticsRepository->getStatistics($newsletter);
    $row = $this->buildAggregateRow($newsletter, $stats);

    $this->ensureExportDirectory();
    $file = ExportDownload::createExportFile(self::FILE_PREFIX, $format);
    $this->writeFile($file['path'], $headers, [$row], $format);

    return [
      'exportFileURL' => $this->getExportFileUrl($file['token'], $format),
      'totalExported' => 1,
    ];
  }

  /**
   * Export aggregate stats for multiple newsletters to CSV or XLSX (one
   * row per newsletter). Used by the bulk export action on the listing.
   *
   * @param NewsletterEntity[] $newsletters
   * @return array{exportFileURL: string, totalExported: int}
   */
  public function exportBulkAggregate(array $newsletters, string $format): array {
    $format = $this->normalizeFormat($format);
    $headers = $this->getAggregateHeaders();

    $rows = [];
    foreach ($newsletters as $newsletter) {
      if (!$newsletter instanceof NewsletterEntity) {
        continue;
      }
      $stats = $this->statisticsRepository->getStatistics($newsletter);
      $rows[] = $this->buildAggregateRow($newsletter, $stats);
    }

    $this->ensureExportDirectory();
    $file = ExportDownload::createExportFile(self::FILE_PREFIX, $format);
    $this->writeFile($file['path'], $headers, $rows, $format);

    return [
      'exportFileURL' => $this->getExportFileUrl($file['token'], $format),
      'totalExported' => count($rows),
    ];
  }

  /**
   * Export per-recipient stats for a newsletter to CSV or XLSX. Rows are
   * populated via the FILTER_RECIPIENT_ROWS filter — the free plugin returns
   * no rows, premium populates them.
   *
   * @return array{exportFileURL: string, totalExported: int}
   */
  public function exportRecipients(NewsletterEntity $newsletter, string $format): array {
    $format = $this->normalizeFormat($format);
    $headers = $this->getRecipientHeaders($newsletter);

    /** @var array<array<int|string|float|null>> $rows */
    $rows = (array)$this->wp->applyFilters(self::FILTER_RECIPIENT_ROWS, [], $newsletter);

    $this->ensureExportDirectory();
    $file = ExportDownload::createExportFile(self::FILE_PREFIX, $format);
    $this->writeFile($file['path'], $headers, $rows, $format);

    return [
      'exportFileURL' => $this->getExportFileUrl($file['token'], $format),
      'totalExported' => count($rows),
    ];
  }

  /**
   * Recipient export headers. For subscriber-timezone campaigns three extra
   * delivery columns are appended; the premium plugin appends the matching
   * row cells in `RecipientsExporter::getRows()` using the same
   * `TimeZoneCampaignScheduler::isTimeZoneQueue()` predicate, so the two
   * MUST stay in sync.
   *
   * @return string[]
   */
  public function getRecipientHeaders(?NewsletterEntity $newsletter = null): array {
    $headers = [
      __('Subscriber ID', 'mailpoet'),
      __('Email', 'mailpoet'),
      __('First name', 'mailpoet'),
      __('Last name', 'mailpoet'),
      __('Status', 'mailpoet'),
      __('Opened', 'mailpoet'),
      __('First open at', 'mailpoet'),
      __('Open count', 'mailpoet'),
      __('Machine opened', 'mailpoet'),
      __('Clicked', 'mailpoet'),
      __('Click count', 'mailpoet'),
      __('Bounced', 'mailpoet'),
      __('Unsubscribed', 'mailpoet'),
    ];
    if ($newsletter && $this->isTimeZoneCampaign($newsletter)) {
      $headers[] = __('Delivery timezone', 'mailpoet');
      $headers[] = __('Timezone fallback used', 'mailpoet');
      $headers[] = __('Local send time', 'mailpoet');
    }
    return $headers;
  }

  private function isTimeZoneCampaign(NewsletterEntity $newsletter): bool {
    $queue = $newsletter->getLatestQueue();
    return $queue !== null && $this->timeZoneCampaignScheduler->isTimeZoneQueue($queue);
  }

  /**
   * @return string[]
   */
  public function getAggregateHeaders(): array {
    return [
      __('Newsletter ID', 'mailpoet'),
      __('Subject', 'mailpoet'),
      __('Campaign name', 'mailpoet'),
      __('Sent at', 'mailpoet'),
      __('Total sent', 'mailpoet'),
      __('Unique opens', 'mailpoet'),
      __('Machine opens', 'mailpoet'),
      __('Unique clicks', 'mailpoet'),
      __('Bounces', 'mailpoet'),
      __('Unsubscribes', 'mailpoet'),
      __('Revenue', 'mailpoet'),
      __('Currency', 'mailpoet'),
      __('Orders', 'mailpoet'),
    ];
  }

  /**
   * Build one aggregate row for a newsletter, in the same column order as
   * `getAggregateHeaders()`. Public so it can be reused by bulk export in phase 3.
   *
   * @return array<int|string|float|null>
   */
  public function buildAggregateRow(NewsletterEntity $newsletter, NewsletterStatistics $stats): array {
    $sentAt = $newsletter->getSentAt();
    $revenue = $stats->getWooCommerceRevenue();

    return [
      (int)$newsletter->getId(),
      (string)$newsletter->getSubject(),
      (string)($newsletter->getCampaignName() ?? ''),
      $sentAt ? $sentAt->format('Y-m-d H:i:s') : '',
      $stats->getTotalSentCount(),
      $stats->getOpenCount(),
      $stats->getMachineOpenCount(),
      $stats->getClickCount(),
      $stats->getBounceCount(),
      $stats->getUnsubscribeCount(),
      $revenue instanceof WooCommerceRevenue ? $revenue->getValue() : '',
      $revenue instanceof WooCommerceRevenue ? $revenue->getCurrency() : '',
      $revenue instanceof WooCommerceRevenue ? $revenue->getOrdersCount() : '',
    ];
  }

  /**
   * @param string[] $headers
   * @param array<array<int|string|float|null>> $rows
   */
  private function writeFile(string $filePath, array $headers, array $rows, string $format): void {
    if ($format === self::FORMAT_XLSX) {
      $this->writeXlsx($filePath, $headers, $rows);
      return;
    }
    $this->writeCsv($filePath, $headers, $rows);
  }

  /**
   * @param string[] $headers
   * @param array<array<int|string|float|null>> $rows
   */
  private function writeCsv(string $filePath, array $headers, array $rows): void {
    $handle = fopen($filePath, 'w');
    if ($handle === false) {
      throw new \RuntimeException('Failed opening file for export.');
    }

    // UTF-8 BOM so Excel auto-detects encoding (matches Subscribers/ImportExport/Export/Export.php)
    fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
    $this->writeCsvLine($handle, $headers);
    foreach ($rows as $row) {
      $this->writeCsvLine($handle, $row);
    }
    fclose($handle);
  }

  /**
   * @param resource $handle
   * @param array<int|string|float|null> $row
   */
  private function writeCsvLine($handle, array $row): void {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Export handles are created under Env::$tempPath, which is MailPoet's WordPress temp directory.
    fputcsv($handle, array_map('strval', $row), ',', '"', '');
  }

  /**
   * @param string[] $headers
   * @param array<array<int|string|float|null>> $rows
   */
  private function writeXlsx(string $filePath, array $headers, array $rows): void {
    $writer = new XLSXWriter();
    $sheetName = __('Statistics', 'mailpoet');
    $writer->writeSheetHeader($sheetName, array_fill_keys($headers, 'string'));
    foreach ($rows as $row) {
      $writer->writeSheetRow($sheetName, $row);
    }
    $writer->writeToFile($filePath);
  }

  private function ensureExportDirectory(): void {
    ExportDownload::ensureExportDirectory($this->wp);
  }

  private function getExportFileUrl(string $token, string $format): string {
    return ExportDownload::buildStatisticsExportUrl($token, $format, $this->wp->homeUrl());
  }

  private function normalizeFormat(string $format): string {
    $format = strtolower($format);
    if ($format !== self::FORMAT_CSV && $format !== self::FORMAT_XLSX) {
      throw new \InvalidArgumentException(sprintf('Unsupported export format "%s".', $format));
    }
    return $format;
  }
}
