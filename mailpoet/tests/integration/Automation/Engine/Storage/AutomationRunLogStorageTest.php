<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;

class AutomationRunLogStorageTest extends \MailPoetTest {

  /** @var AutomationRunLogStorage */
  private $storage;

  /** @var AutomationRunStorage */
  private $runStorage;

  public function _before() {
    $this->storage = $this->diContainer->get(AutomationRunLogStorage::class);
    $this->runStorage = $this->diContainer->get(AutomationRunStorage::class);
  }

  public function testItSavesAndRetrievesAsExpected() {
    $log = new AutomationRunLog(1, 'step-id');
    $log->setData('key', 'value');
    $log->setData('key2', ['arrayData']);
    $preSave = $log->toArray();
    $id = $this->storage->createAutomationRunLog($log);
    $fromDatabase = $this->storage->getAutomationRunLog($id);
    $this->assertInstanceOf(AutomationRunLog::class, $fromDatabase);
    expect($preSave)->equals($fromDatabase->toArray());
  }

  public function testItCanStoreAnError() {
    $log = new AutomationRunLog(1, 'step-id');
    $log->setError(new \Exception('test'));
    $id = $this->storage->createAutomationRunLog($log);
    $log = $this->storage->getAutomationRunLog($id);
    $this->assertInstanceOf(AutomationRunLog::class, $log);
    $errors = $log->getError();
    expect($errors)->array();
    expect(array_keys($errors))->equals([
      'message',
      'errorClass',
      'code',
      'trace',
    ]);
    expect($errors['trace'])->array();
    expect(count($errors['trace']))->greaterThan(0);
  }

  public function testAutomationRunStatisticsInTimeFrame() {
    global $wpdb;
    $expected = [
      [
        'count' => '2',
        'step_id' => 'step-1',
      ],
      [
        'count' => '1',
        'step_id' => 'step-2',
      ],
    ];
    $timeFrame = [
      'after' => new \DateTimeImmutable('2020-01-01 00:00:00'),
      'before' => new \DateTimeImmutable('2020-01-02 00:00:00'),
    ];
    $status = AutomationRunLog::STATUS_COMPLETED;
    $automationId = 1;

    $this->storage->truncate();
    $this->runStorage->truncate();
    $sql = "insert into " . $wpdb->prefix .
            "mailpoet_automation_runs" .
            "(automation_id, version_id, created_at, `status`, trigger_key) values" .
            "($automationId, 1,         '2019-12-31 23:59:59', 'status', 'trigger_key')," . // Automation Run Id 1 outside of timeframe
            "($automationId, 1,         '2020-01-01 00:00:00', 'status', 'trigger_key')," . // Automation Run Id 2
            "($automationId, 2,         '2020-01-02 00:00:00', 'status', 'trigger_key')," . // Automation Run Id 3 different version
            "($automationId, 2,         '2020-01-02 00:00:01', 'status', 'trigger_key')," . // Automation Run Id 4 outside of timeframe
            "(2,             1,         '2020-01-01 00:00:00', 'status', 'trigger_key')"; // Automation Run Id 5 wrong automation id
    $this->assertNotFalse($wpdb->query($sql));
    $sql = "insert into " . $wpdb->prefix .
            "mailpoet_automation_run_logs " .
             "(automation_run_id,  step_id,  `status`) values" .
             "(1,                  'step-0', '$status')," . // Outside of timeframe
             "(2,                  'step-1', '$status')," . // Should find this one
             "(2,                  'step-2', '$status')," . // Should find this one
             "(3,                  'step-1', '$status')," . // Should find this one when version is not set to 1
             "(3,                  'step-2', 'failed')," . // Wrong status
             "(4,                  'step-1', '$status')," . // Outside of timeframe.
             "(5,                  'step-2', '$status')"; // Wrong automation id
    $this->assertNotFalse($wpdb->query($sql));
    $result = $this->storage->getAutomationRunStatisticsForAutomationInTimeFrame(1, $status, $timeFrame['after'], $timeFrame['before']);
    $this->assertEquals(count($expected), count($result));
    $this->assertEquals($expected, $result);

    $versionId = 1;
    $result = $this->storage->getAutomationRunStatisticsForAutomationInTimeFrame(1, $status, $timeFrame['after'], $timeFrame['before'], $versionId);
    $expected = [
      [
        'count' => '1',
        'step_id' => 'step-1',
      ],
      [
        'count' => '1',
        'step_id' => 'step-2',
      ],
    ];
    $this->assertEquals(count($expected), count($result));
    $this->assertEquals($expected, $result);
  }
}
