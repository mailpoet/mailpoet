<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class LogHandlerTest extends \MailPoetTest {
  /** @var LogRepository */
  private $repository;

  /** @var EntityManagerFactory */
  private $entityManagerFactory;

  public function _before() {
    $this->repository = $this->diContainer->get(LogRepository::class);
    $this->entityManagerFactory = $this->diContainer->get(EntityManagerFactory::class);
  }

  public function testItCreatesLog() {
    $logHandler = new LogHandler(
      $this->repository,
      $this->entityManager,
      $this->entityManagerFactory
    );
    $time = new \DateTime();
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => $time,
      'message' => 'some log message',
    ]);

    $log = $this->repository->findOneBy(['name' => 'name'], ['id' => 'desc']);
    $this->assertInstanceOf(LogEntity::class, $log);
    $createdAt = $log->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    expect($createdAt->format('Y-m-d H:i:s'))->equals($time->format('Y-m-d H:i:s'));
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

    $logHandler = new LogHandler(
      $this->repository,
      $this->entityManager,
      $this->entityManagerFactory,
      \MailPoetVendor\Monolog\Logger::DEBUG,
      true,
      $random
    );
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
      'message' => 'some log message',
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

    $logHandler = new LogHandler(
      $this->repository,
      $this->entityManager,
      $this->entityManagerFactory,
      \MailPoetVendor\Monolog\Logger::DEBUG,
      true,
      $random
    );
    $logHandler->handle([
      'level' => \MailPoetVendor\Monolog\Logger::EMERGENCY,
      'extra' => [],
      'context' => [],
      'channel' => 'name',
      'datetime' => new \DateTime(),
      'message' => 'some log message',
    ]);

    $log = $this->repository->findBy(['name' => 'old name keep']);
    expect($log)->notEmpty();
  }

  public function testItResilientToSqlError(): void {
    $entityManager = $this->entityManagerFactory->createEntityManager();
    $logRepository = new LogRepository($entityManager);
    $logHandler = new LogHandler(
      $logRepository,
      $entityManager,
      $this->entityManagerFactory
    );
    $time = new \DateTime();

    try {
      $this->causeErrorLockingEntityManager($entityManager);
    } catch (\Exception $exception) {
      $logHandler->handle([
        'level' => \MailPoetVendor\Monolog\Logger::ERROR,
        'extra' => [],
        'context' => [],
        'channel' => 'name',
        'datetime' => $time,
        'message' => 'some log message',
      ]);
    }

    $log = $logRepository->findOneBy(['name' => 'name'], ['id' => 'desc']);
    $this->assertInstanceOf(LogEntity::class, $log);
    $createdAt = $log->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    expect($createdAt->format('Y-m-d H:i:s'))->equals($time->format('Y-m-d H:i:s'));
  }

  /**
   * Error is caused by unique index on email in the subscribers table
   */
  private function causeErrorLockingEntityManager(EntityManager $entityManager): void {
    for ($i = 1; $i <= 2; $i++) {
      $this->createSubscriber($entityManager, 'user@test.com');
    }
  }

  private function createSubscriber(EntityManager $entityManager, string $email): void {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $entityManager->persist($subscriber);
    $entityManager->flush();
  }
}
