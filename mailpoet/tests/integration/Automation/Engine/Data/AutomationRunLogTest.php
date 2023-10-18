<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use stdClass;

class AutomationRunLogTest extends \MailPoetTest {
  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  public function _before() {
    parent::_before();
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
  }

  public function testItAllowsSettingSimpleData(): void {
    $log = new AutomationRunLog(1, 'step-id', AutomationRunLog::TYPE_ACTION);
    $this->assertSame([], $log->getData());
    $log->setData('key', 'value');
    $data = $log->getData();
    $this->assertCount(1, $data);
    $this->assertSame('value', $data['key']);
  }

  public function testItAllowsSettingArraysOfScalarValues(): void {
    $log = new AutomationRunLog(1, 'step-id', AutomationRunLog::TYPE_ACTION);
    $data = [
      'string',
      11.1,
      10,
      true,
      false,
    ];
    $log->setData('data', $data);
    $this->automationRunLogStorage->createAutomationRunLog($log);
    $retrieved = $this->automationRunLogStorage->getLogsForAutomationRun(1)[0];
    verify($retrieved->getData()['data'])->equals($data);
  }

  public function testItAllowsSettingMultidimensionalArraysOfScalarValues(): void {
    $log = new AutomationRunLog(1, 'step-id', AutomationRunLog::TYPE_ACTION);
    $data = [
      'values' => [
        'string',
        11.1,
        10,
        true,
        false,
      ],
    ];
    $log->setData('data', $data);
    $this->automationRunLogStorage->createAutomationRunLog($log);
    $retrieved = $this->automationRunLogStorage->getLogsForAutomationRun(1)[0];
    verify($retrieved->getData()['data'])->equals($data);
  }

  public function testItDoesNotAllowSettingDataThatIncludesClosures(): void {
    $log = new AutomationRunLog(1, 'step-id', AutomationRunLog::TYPE_ACTION);
    $badData = [
      function() {
        echo 'closures cannot be serialized';
      },
    ];
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('badData', $badData);
    verify($log->getData())->arrayCount(0);
  }

  public function testItDoesNotAllowSettingObjectsForData(): void {
    $log = new AutomationRunLog(1, 'step-id', AutomationRunLog::TYPE_ACTION);
    $object = new stdClass();
    $object->key = 'value';
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('object', $object);
    verify($log->getData())->arrayCount(0);
  }

  public function testItDoesNotAllowSettingMultidimensionalArrayThatContainsNonScalarValue(): void {
    $log = new AutomationRunLog(1, 'step-id', AutomationRunLog::TYPE_ACTION);
    $data = [
      'test' => [
        'multidimensional' => [
          'array' => [
            'values' => [
              new stdClass(),
            ],
          ],
        ],
      ],
    ];
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('data', $data);
    verify($log->getData())->arrayCount(0);
  }
}
