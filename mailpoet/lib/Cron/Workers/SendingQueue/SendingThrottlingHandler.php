<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Monolog\Logger;

class SendingThrottlingHandler {
  public const BATCH_SIZE = 20;
  // Ceiling for templated batches. Sending starts at TEMPLATED_INITIAL_BATCH_SIZE and doubles
  // after every SUCCESS_THRESHOLD_TO_INCREASE successful requests, so a host that cannot prepare
  // or post a large batch within its limits is found out on a small one.
  public const TEMPLATED_BATCH_SIZE = 1500;
  public const TEMPLATED_INITIAL_BATCH_SIZE = 100;
  public const SETTINGS_KEY = 'mta_throttling';
  public const TEMPLATED_SETTINGS_KEY = 'mta_throttling_templated';
  public const SUCCESS_THRESHOLD_TO_INCREASE = 10;

  /** @var Logger */
  private $logger;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var bool */
  private $useTemplatedSending = false;

  public function __construct(
    LoggerFactory $loggerFactory,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->logger = $loggerFactory->getLogger(LoggerFactory::TOPIC_SENDING);
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function setUseTemplatedSending(bool $useTemplatedSending): void {
    $this->useTemplatedSending = $useTemplatedSending;
  }

  public function getBatchSize(): int {
    $throttlingSettings = $this->loadSettings();
    $batchSize = $throttlingSettings['batch_size'] ?? $this->getInitialBatchSize();
    return min((int)$batchSize, $this->getMaxBatchSize());
  }

  private function getInitialBatchSize(): int {
    return $this->useTemplatedSending ? self::TEMPLATED_INITIAL_BATCH_SIZE : $this->getMaxBatchSize();
  }

  private function getMaxBatchSize(): int {
    if ($this->useTemplatedSending) {
      $batchSize = self::TEMPLATED_BATCH_SIZE;
      $serverMax = $this->settings->get(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST);
      if (is_numeric($serverMax) && (int)$serverMax > 0) {
        $batchSize = min($batchSize, (int)$serverMax);
      }
      $filtered = $this->wp->applyFilters('mailpoet_cron_worker_sending_queue_templated_batch_size', $batchSize);
      return is_int($filtered) && $filtered > 0 ? min($filtered, $batchSize) : $batchSize;
    }
    $batchSize = $this->wp->applyFilters('mailpoet_cron_worker_sending_queue_batch_size', self::BATCH_SIZE);
    return is_int($batchSize) ? $batchSize : self::BATCH_SIZE;
  }

  public function throttleBatchSize(): int {
    $batchSize = $this->getBatchSize();
    if ($batchSize > 1) {
      $batchSize = (int)ceil($this->getBatchSize() / 2);
      $throttlingSettings = $this->loadSettings();
      $throttlingSettings['batch_size'] = $batchSize;
      unset($throttlingSettings['success_count']);
      $this->logger->error("MailPoet throttling: decrease batch_size to: {$batchSize}");
      $this->saveSettings($throttlingSettings);
    }

    return $batchSize;
  }

  public function processSuccess(): void {
    $throttlingSettings = $this->loadSettings() ?? [];
    if (!isset($throttlingSettings['batch_size'])) {
      // Rendered sending only stores a batch size while throttled; templated sending always
      // ramps up from its initial size.
      if (!$this->useTemplatedSending) {
        return;
      }
      $throttlingSettings['batch_size'] = self::TEMPLATED_INITIAL_BATCH_SIZE;
    }
    $throttlingSettings['success_count'] = isset($throttlingSettings['success_count']) ? ++$throttlingSettings['success_count'] : 1;
    $this->logger->info("MailPoet throttling: increase success_count to: {$throttlingSettings['success_count']}");
    if ($throttlingSettings['success_count'] >= self::SUCCESS_THRESHOLD_TO_INCREASE) {
      unset($throttlingSettings['success_count']);
      $throttlingSettings['batch_size'] = min($this->getMaxBatchSize(), $throttlingSettings['batch_size'] * 2);
      $this->logger->info("MailPoet throttling: increase batch_size to: {$throttlingSettings['batch_size']}");
      // A templated batch keeps its size stored once at the ceiling, or it would restart small.
      if (!$this->useTemplatedSending && $this->getMaxBatchSize() === $throttlingSettings['batch_size']) {
        unset($throttlingSettings['batch_size']);
      }
    }
    $this->saveSettings($throttlingSettings);
  }

  private function loadSettings(): ?array {
    return $this->settings->get($this->getSettingsKey());
  }

  private function saveSettings(array $settings): void {
    $this->settings->set($this->getSettingsKey(), $settings);
  }

  private function getSettingsKey(): string {
    // Templated and non-templated sending have very different max batch sizes,
    // so they must not share throttling state or they corrupt each other.
    return $this->useTemplatedSending ? self::TEMPLATED_SETTINGS_KEY : self::SETTINGS_KEY;
  }
}
