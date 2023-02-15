<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;

class AutomationTest extends \MailPoetTest {

  /** @var AutomationStorage $storage */
  private $storage;

  public function _before() {
    $this->storage = $this->diContainer->get(AutomationStorage::class);
  }

  public function testMetaDataIsStored() {
    $automation = $this->tester->createAutomation('test');

    $automation->setMeta('foo', 'bar');
    $this->assertEquals('bar', $automation->getMeta('foo'));
    $this->assertEquals(['foo' => 'bar'], $automation->getMetas());
    $this->storage->updateAutomation($automation);

    $storedAutomation = $this->storage->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $storedAutomation);
    $this->assertEquals('bar', $storedAutomation->getMeta('foo'));
  }

  public function testMetaDataIsDeleted() {

    $automation = $this->tester->createAutomation('test');

    $automation->setMeta('foo', 'bar');
    $automation->deleteMeta('foo');
    $this->assertNull($automation->getMeta('foo'));
    $this->storage->updateAutomation($automation);
    $storedAutomation = $this->storage->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $storedAutomation);
    $this->assertNull($storedAutomation->getMeta('foo'));

    $automation->setMeta('foo', 'bar');
    $automation->setMeta('bar', 'baz');
    $automation->deleteMetas();
    $this->assertEmpty($automation->getMetas());
  }

  public function _after() {
    $this->storage->truncate();
  }
}
{

}
