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
    $this->assertNotFalse($wpdb->query($sql)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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

  public function testGetLastAutomationRunsForAutomations() {
    $automation1 = (new \MailPoet\Test\DataFactories\Automation())->withName('Automation 1')->create();
    $automation2 = (new \MailPoet\Test\DataFactories\Automation())->withName('Automation 2')->create();
    $automation3 = (new \MailPoet\Test\DataFactories\Automation())->withName('Automation 3')->create();

    // Create multiple runs for automation1
    $run1 = (new \MailPoet\Test\DataFactories\AutomationRun())
      ->withAutomation($automation1)
      ->withTriggerKey('trigger1')
      ->withStatus(AutomationRun::STATUS_COMPLETE)
      ->withCreatedAt(new \DateTimeImmutable('2020-01-01 10:00:00'))
      ->create();

    $run2 = (new \MailPoet\Test\DataFactories\AutomationRun())
      ->withAutomation($automation1)
      ->withTriggerKey('trigger1')
      ->withStatus(AutomationRun::STATUS_RUNNING)
      ->withCreatedAt(new \DateTimeImmutable('2020-01-02 10:00:00'))
      ->create();

    $run3 = (new \MailPoet\Test\DataFactories\AutomationRun())
      ->withAutomation($automation1)
      ->withTriggerKey('trigger1')
      ->withStatus(AutomationRun::STATUS_COMPLETE)
      ->withCreatedAt(new \DateTimeImmutable('2020-01-03 10:00:00'))
      ->create();

    // Create one run for automation2
    $run4 = (new \MailPoet\Test\DataFactories\AutomationRun())
      ->withAutomation($automation2)
      ->withTriggerKey('trigger2')
      ->withStatus(AutomationRun::STATUS_FAILED)
      ->withCreatedAt(new \DateTimeImmutable('2020-01-01 12:00:00'))
      ->create();

    // No runs for automation3

    $result = $this->testee->getLastAutomationRunsForAutomations($automation1, $automation2, $automation3);

    $this->assertCount(3, $result);
    $this->assertArrayHasKey($automation1->getId(), $result);
    $this->assertArrayHasKey($automation2->getId(), $result);
    $this->assertArrayHasKey($automation3->getId(), $result);

    // Check automation1 has the latest run (run3)
    $this->assertInstanceOf(AutomationRun::class, $result[$automation1->getId()]);
    $this->assertEquals($run3->getId(), $result[$automation1->getId()]->getId());
    $this->assertEquals(AutomationRun::STATUS_COMPLETE, $result[$automation1->getId()]->getStatus());
    $this->assertEquals('2020-01-03 10:00:00', $result[$automation1->getId()]->getCreatedAt()->format('Y-m-d H:i:s'));

    // Check automation2 has the only run (run4)
    $this->assertInstanceOf(AutomationRun::class, $result[$automation2->getId()]);
    $this->assertEquals($run4->getId(), $result[$automation2->getId()]->getId());
    $this->assertEquals(AutomationRun::STATUS_FAILED, $result[$automation2->getId()]->getStatus());

    // Check automation3 has no runs
    $this->assertNull($result[$automation3->getId()]);
  }
}
