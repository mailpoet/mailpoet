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
    $this->assertEquals(['foo' => 'bar'], $automation->getAllMetas());
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
    $automation->deleteAllMetas();
    $this->assertEmpty($automation->getAllMetas());
  }

  public function testAutomationComparisonWorks() {
    $automation = $this->tester->createAutomation('test');
    $automation2 = clone $automation;
    $automation2->setMeta('foo', 'bar');
    $this->assertFalse($automation->equals($automation2));
    $automation2->deleteMeta('foo');
    $this->assertTrue($automation->equals($automation2));
  }

  /**
   * @dataProvider dataForTestFullValidationWorks
   */
  public function testFullValidationWorks($status, $expected) {
    $automation = $this->tester->createAutomation('test');
    $automation->setStatus($status);
    $this->assertEquals($expected, $automation->needsFullValidation());
  }

  public function dataForTestFullValidationWorks(): array {
    return array_map(
      function(string $status): array {
        return [
          'status' => $status,
          'expected' => in_array($status, [Automation::STATUS_ACTIVE, Automation::STATUS_DEACTIVATING], true),
        ];
      },
      Automation::STATUS_ALL
    );
  }

  public function _after() {
    parent::_after();
    $this->storage->truncate();
  }
}
