<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoetVendor\Monolog\Handler\AbstractProcessingHandler;

class LogHandler extends AbstractProcessingHandler {

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    LogRepository $logRepository,
    $level = \MailPoetVendor\Monolog\Logger::DEBUG,
    $bubble = \true
  ) {
    parent::__construct($level, $bubble);
    $this->logRepository = $logRepository;
  }

  protected function write(array $record): void {
    $message = is_string($record['formatted']) ? $record['formatted'] : null;
    $entity = new LogEntity();
    $entity->setName($record['channel']);
    $entity->setLevel((int)$record['level']);
    $entity->setMessage($message);
    $entity->setCreatedAt($record['datetime']);
    $entity->setRawMessage($record['message']);
    $entity->setContext($record['context']);
    $this->logRepository->saveLog($entity);
  }
}
