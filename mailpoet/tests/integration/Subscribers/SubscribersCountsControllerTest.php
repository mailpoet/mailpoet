<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cache\TransientCache;
use MailPoet\Segments\SegmentsRepository;

class SubscribersCountsControllerTest extends \MailPoetTest {

  /** @var SubscribersCountsController */
  private $controller;

  /** @var TransientCache */
  private $cache;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $this->controller = $this->diContainer->get(SubscribersCountsController::class);
    $this->cache = $this->diContainer->get(TransientCache::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->cache->invalidateAllItems();
  }

  public function testRemoveRedundancyKeepsSubscribersWithoutListEntry(): void {
    $key = TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY;
    $segment = $this->segmentsRepository->createOrUpdate('Segment' . rand(0, 10000));
    $segmentId = (int)$segment->getId();

    // id 0 = "subscribers without a list", a valid segment, and an orphan id.
    $this->cache->setItem($key, ['all' => 1], 0);
    $this->cache->setItem($key, ['all' => 2], $segmentId);
    $this->cache->setItem($key, ['all' => 3], $segmentId + 100000);

    $this->controller->removeRedundancyFromStatisticsCache();

    // The without-a-list entry (id 0) must survive — it is not an orphaned segment.
    verify($this->cache->getItem($key, 0))->notNull();
    verify($this->cache->getItem($key, $segmentId))->notNull();
    // The orphan (no matching segment) is removed.
    verify($this->cache->getItem($key, $segmentId + 100000))->null();
  }

  public function _after() {
    parent::_after();
    $this->cache->invalidateAllItems();
  }
}
