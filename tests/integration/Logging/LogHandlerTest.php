<?php

namespace MailPoet\Logging;

use MailPoet\Entities\LogEntity;
use MailPoetVendor\Carbon\Carbon;

class LogHandlerTest extends \MailPoetTest {
  /** @var LogRepository */
  private $repository;

  public function _before() {
    $this->truncateEntity(LogEntity::class);
    $this->repository = $this->diContainer->get(LogRepository::class);
  }

  public function testItCreatesLog() {
    $logHandler = new LogHandler($this->repository);
    $time = new \DateTime();
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => $time,
    ]);

    $log = $this->repository->findOneBy(['name' => 'name'], ['id' => 'desc']);
    assert($log instanceof LogEntity);
    expect($log->getCreatedAt()->format('Y-m-d H:i:s'))->equals($time->format('Y-m-d H:i:s'));
  }

  public function testItPurgesOldLogs() {
    $entity = new LogEntity();
    $entity->setName( 'old name');
    $entity->setLevel(5);
    $entity->setMessage('xyz');
    $entity->setCreatedAt(Carbon::now()->subDays(100));

    $this->repository->persist($entity);
    $this->repository->flush();

    $random = function() {
      return 0;
    };

    $logHandler = new LogHandler($this->repository, \MailPoetVendor\Monolog\Logger::DEBUG, true, $random);
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
    ]);

    $log = $this->repository->findBy(['name' => 'old name']);
    expect($log)->isEmpty();
  }

  public function testItNotPurgesOldLogs() {
    $entity = new LogEntity();
    $entity->setName( 'old name keep');
    $entity->setLevel(5);
    $entity->setMessage('xyz');
    $entity->setCreatedAt(Carbon::now()->subDays(100));

    $this->repository->persist($entity);
    $this->repository->flush();

    $random = function() {
      return 100;
    };

    $logHandler = new LogHandler($this->repository, \MailPoetVendor\Monolog\Logger::DEBUG, true, $random);
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
    ]);

    $log = $this->repository->findBy(['name' => 'old name keep']);
    expect($log)->notEmpty();
  }
}
