<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Control\StepHandler;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Data\WorkflowRunLog;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\WorkflowRunLogStorage;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Util\Security;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WP\Functions as WPFunctions;
use stdClass;

class WorkflowRunLogTest extends \MailPoetTest {

  /** @var WPFunctions */
  private $wp;

  /** @var StepHandler */
  private $stepHandler;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var Registry */
  private $registry;

  /** @var WorkflowRunLogStorage */
  private $workflowRunLogStorage;

  public function _before() {
    parent::_before();

    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
    $this->workflowRunStorage = $this->diContainer->get(WorkflowRunStorage::class);
    $this->workflowRunLogStorage = $this->diContainer->get(WorkflowRunLogStorage::class);
    $this->stepHandler = $this->diContainer->get(StepHandler::class);
    $this->registry = $this->diContainer->get(Registry::class);
    $this->wp = new WPFunctions();
  }

  public function testItAllowsSettingSimpleData(): void {
    $log = new WorkflowRunLog(1, 'step-id');
    $this->assertSame([], $log->getData());
    $log->setData('key', 'value');
    $data = $log->getData();
    $this->assertCount(1, $data);
    $this->assertSame('value', $data['key']);
  }

  public function testItAllowsSettingArraysOfScalarValues(): void {
    $log = new WorkflowRunLog(1, 'step-id');
    $data = [
      'string',
      11.1,
      10,
      true,
      false
    ];
    $log->setData('data', $data);
    $this->workflowRunLogStorage->createWorkflowRunLog($log);
    $retrieved = $this->workflowRunLogStorage->getLogsForWorkflowRun(1)[0];
    expect($retrieved->getData()['data'])->equals($data);
  }

  public function testItAllowsSettingMultidimensionalArraysOfScalarValues(): void {
    $log = new WorkflowRunLog(1, 'step-id');
    $data = [
      'values' => [
        'string',
        11.1,
        10,
        true,
        false
      ]
    ];
    $log->setData('data', $data);
    $this->workflowRunLogStorage->createWorkflowRunLog($log);
    $retrieved = $this->workflowRunLogStorage->getLogsForWorkflowRun(1)[0];
    expect($retrieved->getData()['data'])->equals($data);
  }

  public function testItDoesNotAllowSettingDataThatIncludesClosures(): void {
    $log = new WorkflowRunLog(1, 'step-id');
    $badData = [
      function() {
        echo 'closures cannot be serialized';
      }
    ];
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('badData', $badData);
    expect($log->getData())->count(0);
  }

  public function testItDoesNotAllowSettingObjectsForData(): void {
    $log = new WorkflowRunLog(1, 'step-id');
    $object = new stdClass();
    $object->key = 'value';
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('object', $object);
    expect($log->getData())->count(0);
  }

  public function testItDoesNotAllowSettingMultidimensionalArrayThatContainsNonScalarValue(): void {
    $log = new WorkflowRunLog(1, 'step-id');
    $data = [
      'test' => [
        'multidimensional' => [
          'array' => [
            'values' => [
              new stdClass()
            ]
          ]
        ]
      ]
    ];
    $this->expectException(\InvalidArgumentException::class);
    $log->setData('data', $data);
    expect($log->getData())->count(0);
  }

  public function testItGetsExposedViaAction(): void {
    $this->wp->addAction(Hooks::WORKFLOW_RUN_LOG_AFTER_STEP_RUN, function(WorkflowRunLog $log) {
      $log->setData('test', 'value');
    });
    $workflowRunLogs = $this->getLogsForAction();
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    expect($log->getData()['test'])->equals('value');
  }

  public function testBadActionIntegrationsCannotDerailStepFromRunning() {
    $this->wp->addAction(Hooks::WORKFLOW_RUN_LOG_AFTER_STEP_RUN, function(WorkflowRunLog $log) {
      throw new \Exception('bad integration');
    });
    $workflowRunLogs = $this->getLogsForAction();
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    expect($log->getStatus())->equals(WorkflowRunLog::STATUS_COMPLETED);
  }

  public function testItStoresWorkflowRunAndStepIdsCorrectly() {
    $testAction = $this->getRegisteredTestAction();
    $actionStep = new Step('action-step-id', Step::TYPE_ACTION, $testAction->getKey(), [], []);
    $workflow = new Workflow('test_workflow', [$actionStep], new \WP_User());
    $workflowId = $this->workflowStorage->createWorkflow($workflow);
    // Reload to get additional data post-save
    $workflow = $this->workflowStorage->getWorkflow($workflowId);
    $this->assertInstanceOf(Workflow::class, $workflow);
    $workflowRun = new WorkflowRun($workflowId, $workflow->getVersionId(), 'trigger-key', []);
    $workflowRunId = $this->workflowRunStorage->createWorkflowRun($workflowRun);
    $this->stepHandler->handle([
      'workflow_run_id' => $workflowRunId,
      'step_id' => 'action-step-id'
    ]);

    $log = $this->workflowRunLogStorage->getLogsForWorkflowRun($workflowRunId)[0];
    expect($log->getWorkflowRunId())->equals($workflowRunId);
    expect($log->getStepId())->equals('action-step-id');
  }

  public function testItLogsCompletedStatusCorrectly(): void {
    $workflowRunLogs = $this->getLogsForAction();
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    expect($log->getStatus())->equals('completed');
  }

  public function testItAddsCompletedAtTimestampAfterRunningSuccessfully(): void {
    $this->wp->addAction(Hooks::WORKFLOW_RUN_LOG_AFTER_STEP_RUN, function(WorkflowRunLog $log) {
      expect($log->getCompletedAt())->null();
    });
    $workflowRunLogs = $this->getLogsForAction();
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    expect($log->getCompletedAt())->isInstanceOf(\DateTimeImmutable::class);
  }

  public function testItAddsCompletedAtTimestampAfterFailing(): void {
    $workflowRunLogs = $this->getLogsForAction(function() {
      throw new \Exception('error');
    });
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    expect($log->getCompletedAt())->isInstanceOf(\DateTimeImmutable::class);
  }

  public function testItLogsFailedStatusCorrectly(): void {
    $workflowRunLogs = $this->getLogsForAction(function() {
      throw new \Exception('error');
    });
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    expect($log->getStatus())->equals('failed');
  }

  public function testItIncludesErrorOnFailure(): void {
    $workflowRunLogs = $this->getLogsForAction(function() {
      throw new \Exception('error', 12345);
    });
    expect($workflowRunLogs)->count(1);
    $log = $workflowRunLogs[0];
    $error = $log->getError();
    expect($error['message'])->equals('error');
    expect($error['code'])->equals(12345);
    expect($error['errorClass'])->equals('Exception');
    expect($error['trace'])->array();
    expect(count($error['trace']))->greaterThan(0);
  }

  public function _after() {
    global $wpdb;
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflow_run_logs';
    $wpdb->query($sql);
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflows';
    $wpdb->query($sql);
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflow_versions';
    $wpdb->query($sql);
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflow_runs';
    $wpdb->query($sql);
  }

  private function getLogsForAction($callback = null) {
    if ($callback === null) {
      $callback = function() {
        return true;
      };
    }
    $testAction = $this->getRegisteredTestAction($callback);
    $actionStep = new Step('action-step-id', Step::TYPE_ACTION, $testAction->getKey(), [], []);
    $workflow = new Workflow('test_workflow', [$actionStep], new \WP_User());
    $workflowId = $this->workflowStorage->createWorkflow($workflow);
    // Reload to get additional data post-save
    $workflow = $this->workflowStorage->getWorkflow($workflowId);
    $this->assertInstanceOf(Workflow::class, $workflow);
    $workflowRun = new WorkflowRun($workflowId, $workflow->getVersionId(), 'trigger-key', []);
    $workflowRunId = $this->workflowRunStorage->createWorkflowRun($workflowRun);
    try {
      $this->stepHandler->handle([
        'workflow_run_id' => $workflowRunId,
        'step_id' => 'action-step-id'
      ]);
    } catch (\Exception $e) {
      // allow exceptions so we can test failure states
    }

    return $this->workflowRunLogStorage->getLogsForWorkflowRun($workflowRunId);
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

class TestAction implements Action {

  private $callback;
  private $key;

  public function __construct() {
    $this->key = Security::generateRandomString(10);
  }

  public function setCallback($callback) {
    $this->callback = $callback;
  }


  public function getSubjectKeys(): array {
    return [];
  }

  public function isValid(array $subjects, Step $step, Workflow $workflow): bool {
    return true;
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    if ($this->callback) {
      ($this->callback)($workflow, $workflowRun, $step);
    }
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getName(): string {
    return 'Test Action';
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object();
  }
}
