<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;

class AutomationRunStorageTest extends \MailPoetTest {

  /** @var AutomationRunStorage */
  private $testee;

  public function _before() {
    $this->testee = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testAutomationStepStatisticForTimeFrame() {
    global $wpdb;
    $expected = [
      [
        'count' => '2',
        'next_step_id' => 'step-1',
      ],
      [
        'count' => '1',
        'next_step_id' => 'step-2',
      ],
    ];
    $timeFrame = [
      'after' => new \DateTimeImmutable('2020-01-01 00:00:00'),
      'before' => new \DateTimeImmutable('2020-01-02 00:00:00'),
    ];
    $status = AutomationRun::STATUS_RUNNING;
    $automationId = 1;

    $this->testee->truncate();
    $sql = "insert into " . $wpdb->prefix .
      "mailpoet_automation_runs" .
      "(automation_id, version_id, created_at, `status`, trigger_key, next_step_id) values" .
      "($automationId, 1,         '2019-12-31 23:59:59', '$status', 'trigger_key', 'step-1')," . // Outside of timeframe
      "($automationId, 1,         '2020-01-01 00:00:00', '$status', 'trigger_key', 'step-1')," . // Should match
      "($automationId, 1,         '2020-01-01 00:00:00', '$status', 'trigger_key', 'step-2')," . // Should match
      "($automationId, 2,         '2020-01-02 00:00:00', '$status', 'trigger_key', 'step-1')," . // Should match when version not 1
      "($automationId, 2,         '2020-01-02 00:00:01', '$status', 'trigger_key', 'step-1')," . // Outside of timeframe
      "($automationId, 1,         '2020-01-01 00:00:00', 'complete', 'trigger_key', 'step-1')," . // Wrong status
      "(2,             1,         '2020-01-01 00:00:00', '$status', 'trigger_key', 'step-1')"; // Wrong automation id
    $this->assertNotFalse($wpdb->query($sql));

    $result = $this->testee->getAutomationStepStatisticForTimeFrame($automationId, $status, $timeFrame['after'], $timeFrame['before']);
    $this->assertEquals($expected, $result);

    $versionId = 1;
    $result = $this->testee->getAutomationStepStatisticForTimeFrame($automationId, $status, $timeFrame['after'], $timeFrame['before'], $versionId);

    $expected = [
      [
        'count' => '1',
        'next_step_id' => 'step-1',
      ],
      [
        'count' => '1',
        'next_step_id' => 'step-2',
      ],
    ];
    $this->assertEquals($expected, $result);
  }
}
