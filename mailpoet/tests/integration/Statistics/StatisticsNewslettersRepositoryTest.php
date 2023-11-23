<?php declare(strict_types = 1);

namespace integration\Statistics;

use MailPoet\Statistics\StatisticsNewslettersRepository;

class StatisticsNewslettersRepositoryTest extends \MailPoetTest {
  /** @var StatisticsNewslettersRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(StatisticsNewslettersRepository::class);
  }

  public function testItCanCreateMultipleStats(): void {
    $data = [
      [
        'newsletter_id' => 1,
        'subscriber_id' => 1,
        'queue_id' => 1,
      ],
      [
        'newsletter_id' => 2,
        'subscriber_id' => 2,
        'queue_id' => 2,
      ],
    ];
    $this->repository->createMultiple($data);
    $this->assertCount(2, $this->repository->findAll());
  }
}
