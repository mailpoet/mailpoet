<?php declare(strict_types = 1);

namespace MailPoet\Test\Statistics;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\StatisticsUnsubscribes;
use MailPoet\Test\DataFactories\Subscriber;

class StatisticsUnsubscribesRepositoryTest extends \MailPoetTest {
  /** @var StatisticsUnsubscribesRepository */
  private $repository;

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before(): void {
    $this->repository = $this->diContainer->get(StatisticsUnsubscribesRepository::class);
    $this->newsletter = (new Newsletter())->withSendingQueue()->create();
  }

  public function testItCountsUnsubscribesPerMonthAndMethodSinceDate(): void {
    $this->createUnsubscribe('2026-03-02 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK);
    $this->createUnsubscribe('2026-03-30 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK);
    $this->createUnsubscribe('2026-03-15 10:00:00', StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
    $this->createUnsubscribe('2026-05-01 00:00:00', StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
    $this->createUnsubscribe('2026-02-28 23:59:59', StatisticsUnsubscribeEntity::METHOD_LINK);

    $result = $this->repository->getMonthlyCountsPerMethod(new \DateTimeImmutable('2026-03-01 00:00:00'));

    verify($result)->equals([
      ['month' => '2026-03', 'method' => StatisticsUnsubscribeEntity::METHOD_LINK, 'count' => 2],
      ['month' => '2026-03', 'method' => StatisticsUnsubscribeEntity::METHOD_ONE_CLICK, 'count' => 1],
      ['month' => '2026-05', 'method' => StatisticsUnsubscribeEntity::METHOD_ONE_CLICK, 'count' => 1],
    ]);
  }

  public function testItCountsUnsubscribesPerMonthMethodSourceAndReasonWithDefaultsForMissingValues(): void {
    $this->createUnsubscribe('2026-03-02 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK, StatisticsUnsubscribeEntity::REASON_SPAM, StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER);
    $this->createUnsubscribe('2026-03-03 10:00:00', StatisticsUnsubscribeEntity::METHOD_LINK, StatisticsUnsubscribeEntity::REASON_SPAM, StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER);
    $this->createUnsubscribe('2026-03-04 10:00:00', StatisticsUnsubscribeEntity::METHOD_UNKNOWN, null, StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR);
    $this->createUnsubscribe('2026-05-01 00:00:00', StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);
    $this->createUnsubscribe('2026-02-28 23:59:59', StatisticsUnsubscribeEntity::METHOD_LINK, StatisticsUnsubscribeEntity::REASON_OTHER);
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE {$this->repository->getTableName()} SET source = NULL WHERE created_at = '2026-05-01 00:00:00'"
    );

    $result = $this->repository->getMonthlyCountsPerMethodSourceAndReason(new \DateTimeImmutable('2026-03-01 00:00:00'));

    verify($result)->equals([
      [
        'month' => '2026-03',
        'method' => StatisticsUnsubscribeEntity::METHOD_LINK,
        'source' => StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER,
        'reason' => StatisticsUnsubscribeEntity::REASON_SPAM,
        'count' => 2,
      ],
      [
        'month' => '2026-03',
        'method' => StatisticsUnsubscribeEntity::METHOD_UNKNOWN,
        'source' => StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR,
        'reason' => StatisticsUnsubscribeEntity::REASON_UNSPECIFIED,
        'count' => 1,
      ],
      [
        'month' => '2026-05',
        'method' => StatisticsUnsubscribeEntity::METHOD_ONE_CLICK,
        'source' => 'unknown',
        'reason' => StatisticsUnsubscribeEntity::REASON_UNSPECIFIED,
        'count' => 1,
      ],
    ]);
  }

  private function createUnsubscribe(string $createdAt, string $method, ?string $reason = null, ?string $source = null): void {
    $subscriber = (new Subscriber())->create();
    $unsubscribe = (new StatisticsUnsubscribes($this->newsletter, $subscriber))
      ->withCreatedAt(new \DateTimeImmutable($createdAt))
      ->create();
    $unsubscribe->setMethod($method);
    if ($source !== null) {
      $unsubscribe->setSource($source);
    }
    if ($reason !== null) {
      $unsubscribe->setReason($reason);
    }
    $this->entityManager->flush();
  }
}
