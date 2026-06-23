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
      wp_die(esc_html__('You do not have permission to download logs.', 'mailpoet'), '', ['response' => 403]);
    }

    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (!$this->wp->wpVerifyNonce($nonce, self::NONCE_ACTION)) {
      wp_die(esc_html__('Security check failed.', 'mailpoet'), '', ['response' => 403]);
    }

    $from = isset($_GET['from']) && is_string($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : null;
    $to = isset($_GET['to']) && is_string($_GET['to']) ? sanitize_text_field(wp_unslash($_GET['to'])) : null;

    $dateFrom = $this->parseDateParam($from);
    $dateTo = $this->parseDateParam($to);

    $logs = $this->logRepository->getLogs($dateFrom, $dateTo, null, null, (string)self::MAX_LOGS);

    $filename = 'mailpoet-logs';
    if ($from) {
      $filename .= '-from-' . $from;
    }
    if ($to) {
      $filename .= '-to-' . $to;
    }
    $filename .= '.txt';

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    foreach ($logs as $log) {
      $createdAt = $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : 'N/A';
      $name = $log->getName() ?? '';
      $message = $log->getMessage() ?? '';
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo '[' . $createdAt . '] [' . $name . '] ' . $message . "\n";
    }
    exit;
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
