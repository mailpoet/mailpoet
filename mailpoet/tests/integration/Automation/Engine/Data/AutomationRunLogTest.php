<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Control\StepHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Test\Automation\Stubs\TestAction;
use MailPoet\WP\Functions as WPFunctions;
use stdClass;

require_once __DIR__ . '/../../Stubs/TestAction.php';
//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Generic.Files.OneClassPerFile.MultipleFound, PSR1.Classes.ClassDeclaration.MultipleClasses

class AutomationRunLogTest extends \MailPoetTest {

  /** @var WPFunctions */
  private $wp;

  /** @var StepHandler */
  private $stepHandler;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var Registry */
  private $registry;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  public function _before() {
    parent::_before();

    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
    $this->stepHandler = $this->diContainer->get(StepHandler::class);
    $this->registry = $this->diContainer->get(Registry::class);
    $this->wp = new WPFunctions();
  }

  public function testItAllowsSettingSimpleData(): void {
    $log = new AutomationRunLog(1, 'step-id');
    $this->assertSame([], $log->getData());
    $log->setData('key', 'value');
    $data = $log->getData();
    $this->assertCount(1, $data);
    $this->assertSame('value', $data['key']);
  }

  public function testItAllowsSettingArraysOfScalarValues(): void {
    $log = new AutomationRunLog(1, 'step-id');
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
    expect($retrieved->getData()['data'])->equals($data);
  }

  public function testItAllowsSettingMultidimensionalArraysOfScalarValues(): void {
    $log = new AutomationRunLog(1, 'step-id');
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
    expect($retrieved->getData()['data'])->equals($data);
  }

  public function testItDoesNotAllowSettingDataThatIncludesClosures(): void {
    $log = new AutomationRunLog(1, 'step-id');
    $badData = [
      function() {
        echo 'closures cannot be serialized';
      },
    ];
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('badData', $badData);
    expect($log->getData())->count(0);
  }

  public function testItDoesNotAllowSettingObjectsForData(): void {
    $log = new AutomationRunLog(1, 'step-id');
    $object = new stdClass();
    $object->key = 'value';
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('object', $object);
    expect($log->getData())->count(0);
  }

  public function testItDoesNotAllowSettingMultidimensionalArrayThatContainsNonScalarValue(): void {
    $log = new AutomationRunLog(1, 'step-id');
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
    expect($log->getData())->count(0);
  }

  public function testItGetsExposedViaAction(): void {
    $this->wp->addAction(Hooks::AUTOMATION_RUN_LOG_AFTER_STEP_RUN, function(AutomationRunLog $log) {
      $log->setData('test', 'value');
    });
    $automationRunLogs = $this->getLogsForAction();
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    expect($log->getData()['test'])->equals('value');
  }

  public function testBadActionIntegrationsCannotDerailStepFromRunning() {
    $this->wp->addAction(Hooks::AUTOMATION_RUN_LOG_AFTER_STEP_RUN, function(AutomationRunLog $log) {
      throw new \Exception('bad integration');
    });
    $automationRunLogs = $this->getLogsForAction();
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    expect($log->getStatus())->equals(AutomationRunLog::STATUS_COMPLETED);
  }

  public function testItStoresAutomationRunAndStepIdsCorrectly() {
    $testAction = $this->getRegisteredTestAction();
    $actionStep = new Step('action-step-id', Step::TYPE_ACTION, $testAction->getKey(), [], []);
    $automation = new Automation('test_automation', [$actionStep->getId() => $actionStep], new \WP_User());
    $automation->setStatus(Automation::STATUS_ACTIVE);
    $automationId = $this->automationStorage->createAutomation($automation);
    // Reload to get additional data post-save
    $automation = $this->automationStorage->getAutomation($automationId);
    $this->assertInstanceOf(Automation::class, $automation);
    $automationRun = new AutomationRun($automationId, $automation->getVersionId(), 'trigger-key', []);
    $automationRunId = $this->automationRunStorage->createAutomationRun($automationRun);
    $this->stepHandler->handle([
      'automation_run_id' => $automationRunId,
      'step_id' => 'action-step-id',
    ]);

    $log = $this->automationRunLogStorage->getLogsForAutomationRun($automationRunId)[0];
    expect($log->getAutomationRunId())->equals($automationRunId);
    expect($log->getStepId())->equals('action-step-id');
  }

  public function testItLogsCompletedStatusCorrectly(): void {
    $automationRunLogs = $this->getLogsForAction();
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    expect($log->getStatus())->equals('completed');
  }

  public function testItAddsCompletedAtTimestampAfterRunningSuccessfully(): void {
    $this->wp->addAction(Hooks::AUTOMATION_RUN_LOG_AFTER_STEP_RUN, function(AutomationRunLog $log) {
      expect($log->getCompletedAt())->null();
    });
    $automationRunLogs = $this->getLogsForAction();
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    expect($log->getCompletedAt())->isInstanceOf(\DateTimeImmutable::class);
  }

  public function testItAddsCompletedAtTimestampAfterFailing(): void {
    $automationRunLogs = $this->getLogsForAction(function() {
      throw new \Exception('error');
    });
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    expect($log->getCompletedAt())->isInstanceOf(\DateTimeImmutable::class);
  }

  public function testItLogsFailedStatusCorrectly(): void {
    $automationRunLogs = $this->getLogsForAction(function() {
      throw new \Exception('error');
    });
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    expect($log->getStatus())->equals('failed');
  }

  public function testItIncludesErrorOnFailure(): void {
    $automationRunLogs = $this->getLogsForAction(function() {
      throw new \Exception('error', 12345);
    });
    expect($automationRunLogs)->count(1);
    $log = $automationRunLogs[0];
    $error = $log->getError();
    expect($error['message'])->equals('error');
    expect($error['code'])->equals(12345);
    expect($error['errorClass'])->equals('Exception');
    expect($error['trace'])->array();
    expect(count($error['trace']))->greaterThan(0);
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
  }

  private function getLogsForAction($callback = null) {
    if ($callback === null) {
      $callback = function() {
        return true;
      };
    }
    $testAction = $this->getRegisteredTestAction($callback);
    $actionStep = new Step('action-step-id', Step::TYPE_ACTION, $testAction->getKey(), [], []);
    $automation = new Automation('test_automation', [$actionStep->getId() => $actionStep], new \WP_User());
    $automation->setStatus(Automation::STATUS_ACTIVE);
    $automationId = $this->automationStorage->createAutomation($automation);
    // Reload to get additional data post-save
    $automation = $this->automationStorage->getAutomation($automationId);
    $this->assertInstanceOf(Automation::class, $automation);
    $automationRun = new AutomationRun($automationId, $automation->getVersionId(), 'trigger-key', []);
    $automationRunId = $this->automationRunStorage->createAutomationRun($automationRun);
    try {
      $this->stepHandler->handle([
        'automation_run_id' => $automationRunId,
        'step_id' => 'action-step-id',
      ]);
    } catch (\Exception $e) {
      // allow exceptions so we can test failure states
    }

    return $this->automationRunLogStorage->getLogsForAutomationRun($automationRunId);
  }

  private function getRegisteredTestAction($callback = null) {
    if ($callback === null) {
      $callback = function() {
        return true;
      };
    }
    $action = new TestAction();
    $action->setCallback($callback);
    $this->registry->addAction($action);

    return $action;
  }
}
