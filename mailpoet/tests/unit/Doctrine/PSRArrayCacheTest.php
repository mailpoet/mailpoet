<?php declare(strict_types = 1);

namespace unit\Doctrine;

use MailPoet\Doctrine\PSRArrayCache;
use MailPoet\Doctrine\PSRCacheItem;
use MailPoetUnitTest;

class PSRArrayCacheTest extends MailPoetUnitTest {
  public function testGetItemReturnsMissForUnknownKey(): void {
    $cache = new PSRArrayCache();

    $item = $cache->getItem('missing');

    $this->assertInstanceOf(PSRCacheItem::class, $item);
    $this->assertSame('missing', $item->getKey());
    $this->assertFalse($item->isHit());
    $this->assertNull($item->get());
  }

  public function testSavedItemIsRetrievedAsHitWithStoredValue(): void {
    $cache = new PSRArrayCache();
    $stored = ['payload' => 'value'];

    $item = $cache->getItem('present');
    $item->set($stored);
    $cache->save($item);

    $retrieved = $cache->getItem('present');

    $this->assertTrue($retrieved->isHit());
    $this->assertSame($stored, $retrieved->get());
  }

  public function testFalsyValueIsStillReportedAsHit(): void {
    $cache = new PSRArrayCache();

    $item = $cache->getItem('falsy');
    $item->set(false);
    $cache->save($item);

    $retrieved = $cache->getItem('falsy');

    $this->assertTrue($retrieved->isHit());
    $this->assertFalse($retrieved->get());
  }

  public function testDeletedItemIsReportedAsMiss(): void {
    $cache = new PSRArrayCache();
    $item = $cache->getItem('temp');
    $item->set('payload');
    $cache->save($item);

    $cache->deleteItem('temp');

    $retrieved = $cache->getItem('temp');
    $this->assertFalse($retrieved->isHit());
    $this->assertNull($retrieved->get());
  }
}
