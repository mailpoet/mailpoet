<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Statistics\Export;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\XLSXWriter;

class StatisticsExporter {
  public const FORMAT_CSV = 'csv';
  public const FORMAT_XLSX = 'xlsx';

  private const FILE_PREFIX = 'MailPoet_stats_export_';
  private const RANDOM_NAME_LENGTH = 15;

  /** @var NewsletterStatisticsRepository */
  private $statisticsRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterStatisticsRepository $statisticsRepository,
    WPFunctions $wp
  ) {
    $this->statisticsRepository = $statisticsRepository;
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
    $filePath = $this->getExportFilePath($format);
    $this->writeFile($filePath, $headers, [$row], $format);

    return [
      'exportFileURL' => $this->getExportFileUrl(basename($filePath)),
      'totalExported' => 1,
    ];
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
    $path = Env::$tempPath;
    if (!is_dir($path)) {
      $this->wp->wpMkdirP($path);
    }
  }

  private function getExportFilePath(string $format): string {
    return Env::$tempPath . '/' . self::FILE_PREFIX . Security::generateRandomString(self::RANDOM_NAME_LENGTH) . '.' . $format;
  }

  private function getExportFileUrl(string $filename): string {
    return Env::$tempUrl . '/' . $filename;
  }

  private function normalizeFormat(string $format): string {
    $format = strtolower($format);
    if ($format !== self::FORMAT_CSV && $format !== self::FORMAT_XLSX) {
      throw new \InvalidArgumentException(sprintf('Unsupported export format "%s".', $format));
    }
    return $format;
  }
}
