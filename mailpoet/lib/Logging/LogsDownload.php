<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class LogsDownload {
  private const ACTION = 'mailpoet_download_logs';
  private const NONCE_ACTION = 'mailpoet_download_logs_nonce';
  private const MAX_LOGS = 50000;

  /** @var LogRepository */
  private $logRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    LogRepository $logRepository,
    WPFunctions $wp
  ) {
    $this->logRepository = $logRepository;
    $this->wp = $wp;
  }

  public function initialize(): void {
    $this->wp->addAction('admin_post_' . self::ACTION, [$this, 'handle']);
  }

  public function handle(): void {
    if (!$this->wp->currentUserCan(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN)) {
      $this->wp->wpDie(esc_html__('You do not have permission to download logs.', 'mailpoet'), '', ['response' => 403]);
    }

    $nonce = $this->getStringParam('_wpnonce') ?? '';
    if (!$this->wp->wpVerifyNonce($nonce, self::NONCE_ACTION)) {
      $this->wp->wpDie(esc_html__('Security check failed.', 'mailpoet'), '', ['response' => 403]);
    }

    $from = $this->getStringParam('from');
    $to = $this->getStringParam('to');
    $search = $this->getStringParam('search');

    $filter = [];
    $dateFrom = $this->parseDateParam($from);
    if ($dateFrom instanceof \DateTimeImmutable) {
      $filter['from'] = $dateFrom->format('Y-m-d');
    }
    $dateTo = $this->parseDateParam($to);
    if ($dateTo instanceof \DateTimeImmutable) {
      $filter['to'] = $dateTo->format('Y-m-d');
    }
    $names = $this->parseStringArrayParam('name');
    if ($names) {
      $filter['name'] = $names;
    }
    $levels = $this->parseIntArrayParam('level');
    if ($levels) {
      $filter['level'] = $levels;
    }
    if ($search === '') {
      $search = null;
    }

    $logs = $this->logRepository->getLogsForExport($filter, $search, self::MAX_LOGS);

    $filename = 'mailpoet-logs';
    if (isset($filter['from'])) {
      $filename .= '-from-' . $filter['from'];
    }
    if (isset($filter['to'])) {
      $filename .= '-to-' . $filter['to'];
    }
    $filename .= '.txt';

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    foreach ($logs as $log) {
      $createdAt = $log['created_at'] ?? 'N/A';
      $name = $log['name'] ?? '';
      $message = $log['message'] ?? '';
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo '[' . $createdAt . '] [' . $name . '] ' . $message . "\n";
    }
    exit;
  }

  private function getStringParam(string $key): ?string {
    if (!isset($_GET[$key])) {
      return null;
    }
    $value = $this->wp->wpUnslash($_GET[$key]);
    return is_string($value) ? $this->wp->sanitizeTextField($value) : null;
  }

  /**
   * @return string[]
   */
  private function parseStringArrayParam(string $key): array {
    if (!isset($_GET[$key])) {
      return [];
    }
    $unslashed = $this->wp->wpUnslash($_GET[$key]);
    if (!is_array($unslashed)) {
      return [];
    }
    $values = [];
    foreach ($unslashed as $value) {
      if (is_string($value) && $value !== '') {
        $values[] = $this->wp->sanitizeTextField($value);
      }
    }
    return array_values(array_unique($values));
  }

  /**
   * @return int[]
   */
  private function parseIntArrayParam(string $key): array {
    if (!isset($_GET[$key]) || !is_array($_GET[$key])) {
      return [];
    }
    $values = [];
    foreach ($_GET[$key] as $value) {
      if (is_numeric($value)) {
        $values[] = (int)$value;
      }
    }
    return array_values(array_unique($values));
  }

  public static function createNonce(WPFunctions $wp): string {
    return $wp->wpCreateNonce(self::NONCE_ACTION);
  }

  private function parseDateParam(?string $value): ?\DateTimeImmutable {
    if ($value === null || $value === '') {
      return null;
    }
    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date) {
      return null;
    }
    $errors = \DateTimeImmutable::getLastErrors();
    if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
      return null;
    }
    return $date;
  }
}
