<?php

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoetVendor\Monolog\Handler\AbstractProcessingHandler;

class LogHandler extends AbstractProcessingHandler {
  /**
   * Logs older than this many days will be deleted
   */
  const DAYS_TO_KEEP_LOGS = 30;

  /**
   * Percentage value, what is the probability of running purge routine
   * @var int
   */
  const LOG_PURGE_PROBABILITY = 5;

  /** @var callable|null */
  private $randFunction;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    LogRepository $logRepository,
    $level = \MailPoetVendor\Monolog\Logger::DEBUG,
    $bubble = \true,
    $randFunction = null
  ) {
    parent::__construct($level, $bubble);
    $this->randFunction = $randFunction;
    $this->logRepository = $logRepository;
  }

  protected function write(array $record) {
    $entity = new LogEntity();
    $entity->setName($record['channel']);
    $entity->setLevel($record['level']);
    $entity->setMessage($record['formatted']);
    $entity->setCreatedAt($record['datetime']);

    $this->logRepository->persist($entity);
    $this->logRepository->flush();

    if ($this->getRandom() <= self::LOG_PURGE_PROBABILITY) {
      $this->purgeOldLogs();
    }
  }

  private function getRandom() {
    if ($this->randFunction) {
      return call_user_func($this->randFunction, 0, 100);
    }
    return rand(0, 100);
  }

  private function purgeOldLogs() {
    $this->logRepository->purgeOldLogs(self::DAYS_TO_KEEP_LOGS);
  }
}
