<?php declare(strict_types = 1);

namespace MailPoet\Test\DataGenerator;

use MailPoet\Test\DataGenerator\Generators\SampleData;
use MailPoet\Test\DataGenerator\Generators\SampleDataConfig;

class DataGenerator {

  /** @var callable|null */
  private $logger;

  /** @var callable|null */
  private $exceptionLogger;

  public function __construct(
    ?callable $logger = null,
    ?callable $exceptionLogger = null
  ) {
    $this->logger = $logger;
    $this->exceptionLogger = $exceptionLogger;
  }

  /**
   * @param array<string, mixed> $options
   */
  public function run(array $options = []) {
    ini_set('memory_limit', '1024M'); // phpcs:ignore QITStandard.PHP.DebugCode.DangerousIniSet
    $timer = time();
    try {
      $generator = $this->createGenerator($options);
      foreach ($generator->generate() as $message) {
        $this->log($timer, $message);
      }
    } catch (\Throwable $e) {
      $this->logException($e);
    }
    $this->log($timer, 'DONE!');
  }

  /**
   * @param array<string, mixed> $options
   */
  public function runBefore(array $options = []) {
    $this->createGenerator($options)->runBefore();
  }

  /**
   * @param array<string, mixed> $options
   */
  public function runAfter(array $options = []) {
    $this->createGenerator($options)->runAfter();
  }

  /**
   * @param array<string, mixed> $options
   */
  private function createGenerator(array $options): SampleData {
    return new SampleData(SampleDataConfig::fromArray($options));
  }

  private function log($timer, $message): void {
    if (!$this->logger) {
      return;
    }
    $duration = time() - $timer;
    $memory = round(memory_get_usage() / 1048576);
    call_user_func($this->logger, "[{$duration}s][{$memory}MB] $message");
  }

  private function logException(\Throwable $e): void {
    if ($this->exceptionLogger) {
      call_user_func($this->exceptionLogger, $e);
      return;
    }
    if ($this->logger) {
      call_user_func($this->logger, $e->getMessage());
      call_user_func($this->logger, $e->getTraceAsString());
    }
  }
}
