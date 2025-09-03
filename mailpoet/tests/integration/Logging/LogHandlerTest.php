<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\SubscriberEntity;
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
    $logHandler = new LogHandler($this->repository);
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
    verify($createdAt->format('Y-m-d H:i:s'))->equals($time->format('Y-m-d H:i:s'));
  }

  public function testItResilientToSqlError(): void {
    $entityManager = $this->entityManagerFactory->createEntityManager();
    $logRepository = new LogRepository($entityManager);
    $logHandler = new LogHandler($logRepository);
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
    verify($createdAt->format('Y-m-d H:i:s'))->equals($time->format('Y-m-d H:i:s'));
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
