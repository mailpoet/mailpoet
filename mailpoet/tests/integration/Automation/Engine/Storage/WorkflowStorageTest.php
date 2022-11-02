<?php

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Data\WorkflowRunLog;
use MailPoet\Automation\Engine\Storage\WorkflowRunLogStorage;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;

class WorkflowStorageTest extends \MailPoetTest
{

  /** @var WorkflowStorage */
  private $testee;

  public function _before() {
    $this->testee = $this->diContainer->get(WorkflowStorage::class);
  }

  public function testItLoadsLatestVersion() {
    $workflow = $this->createEmptyWorkflow();

    $step1 = new Step('id', Step::TYPE_ACTION, 'key', [], []);
    $workflow->setSteps(['id' => $step1]);
    $this->testee->updateWorkflow($workflow);
    $updatedWorkflow = $this->testee->getWorkflow($workflow->getId());
    $this->assertInstanceOf(Workflow::class, $updatedWorkflow);
    $this->assertTrue($workflow->getVersionId() < $updatedWorkflow->getVersionId());
    $this->assertEquals(1, count($updatedWorkflow->getSteps()));

    $step2 = new Step('id-2', Step::TYPE_ACTION, 'key', [], []);
    $workflow->setSteps(['id' => $step1, 'id-2' => $step2]);
    $this->testee->updateWorkflow($workflow);
    $latestWorkflow = $this->testee->getWorkflow($workflow->getId());
    $this->assertInstanceOf(Workflow::class, $latestWorkflow);
    $this->assertTrue($updatedWorkflow->getVersionId() < $latestWorkflow->getVersionId());
    $this->assertEquals(2, count($latestWorkflow->getSteps()));
  }

  public function testItLoadsCorrectVersion() {
    $workflow = $this->createEmptyWorkflow();

    $step1 = new Step('id', Step::TYPE_ACTION, 'key', [], []);
    $workflow->setSteps(['id' => $step1]);
    $this->testee->updateWorkflow($workflow);
    $updatedWorkflow = $this->testee->getWorkflow($workflow->getId());
    $this->assertInstanceOf(Workflow::class, $updatedWorkflow);
    $this->assertTrue($workflow->getVersionId() < $updatedWorkflow->getVersionId());
    $this->assertEquals(1, count($updatedWorkflow->getSteps()));

    $step2 = new Step('id-2', Step::TYPE_ACTION, 'key', [], []);
    $workflow->setSteps(['id' => $step1, 'id-2' => $step2]);
    $this->testee->updateWorkflow($workflow);
    $correctWorkflow = $this->testee->getWorkflow($workflow->getId(), $updatedWorkflow->getVersionId());
    $this->assertInstanceOf(Workflow::class, $correctWorkflow);
    $this->assertTrue($updatedWorkflow->getVersionId() === $correctWorkflow->getVersionId());
    $this->assertEquals($updatedWorkflow->getSteps(), $correctWorkflow->getSteps());
  }

  public function testItLoadsOnlyActiveWorkflowsByTrigger() {
    $workflow = $this->createEmptyWorkflow();
    $subscriberTrigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $trigger = new Step('id', Step::TYPE_TRIGGER, $subscriberTrigger->getKey(), [], []);
    $workflow->setSteps(['id' => $trigger]);
    $workflow->setStatus(Workflow::STATUS_INACTIVE);
    $this->testee->updateWorkflow($workflow);
    $this->assertEmpty($this->testee->getActiveWorkflowsByTrigger($subscriberTrigger));
    $workflow->setStatus(Workflow::STATUS_ACTIVE);
    $this->testee->updateWorkflow($workflow);
    $this->assertCount(1, $this->testee->getActiveWorkflowsByTrigger($subscriberTrigger));
    $workflow->setStatus(Workflow::STATUS_INACTIVE);
    $this->testee->updateWorkflow($workflow);
    $this->assertEmpty($this->testee->getActiveWorkflowsByTrigger($subscriberTrigger));
  }

  public function testItCanDeleteAWorkflow() {
    $workflowToDelete = $this->createEmptyWorkflow();
    $workflowToKeep = $this->createEmptyWorkflow();
    expect($this->testee->getWorkflows())->count(2);
    $this->testee->deleteWorkflow($workflowToDelete);
    expect($this->testee->getWorkflows())->count(1);
    expect($this->testee->getWorkflow($workflowToDelete->getId()))->null();
    $workflowToKeepFromDatabase = $this->testee->getWorkflow($workflowToKeep->getId());
    $this->assertInstanceOf(Workflow::class, $workflowToKeepFromDatabase);
    expect($workflowToKeepFromDatabase->getVersionId())->notNull();
  }

  public function testItCanDeleteWorkflowsRelatedData() {
    $workflowRunStorage = $this->diContainer->get(WorkflowRunStorage::class);
    $workflowRunLogStorage = $this->diContainer->get(WorkflowRunLogStorage::class);
    $workflows = [
      'toDelete' => $this->createEmptyWorkflow(),
      'toKeep' => $this->createEmptyWorkflow()
    ];
    $runs = [
      'toDelete' => [],
      'toKeep' => []
    ];
    $runLogs = [
      'toDelete' => [],
      'toKeep' => []
    ];
    foreach ($workflows as $type => $workflow) {
      for ($i = 0; $i < 2; $i++) {
        $workflowRun = new WorkflowRun($workflow->getId(), $workflow->getVersionId(), 'trigger-key', []);
        $runId = $workflowRunStorage->createWorkflowRun($workflowRun);
        $runs[$type][] = $runId;
        for ($i = 0; $i < 2; $i++) {
          $log = new WorkflowRunLog($runId, "step-{$i}");
          $logId = $workflowRunLogStorage->createWorkflowRunLog($log);
          $runLogs[$type][] = $logId;
        }
      }
    }
    $this->testee->deleteWorkflow($workflows['toDelete']);
    foreach ($runs['toDelete'] as $runId) {
      expect($workflowRunStorage->getWorkflowRun($runId))->null();
    }
    foreach ($runs['toKeep'] as $runId) {
      expect($workflowRunStorage->getWorkflowRun($runId))->notNull();
    }
    foreach ($runLogs['toDelete'] as $runLogId) {
      expect($workflowRunLogStorage->getWorkflowRunLog($runLogId))->null();
    }
    foreach ($runLogs['toKeep'] as $runLogId) {
      expect($workflowRunLogStorage->getWorkflowRunLog($runLogId))->notNull();
    }
  }

  private function createEmptyWorkflow(string $name="test"): Workflow {
    $workflow = new Workflow($name, [], new \WP_User());
    $workflowId = $this->testee->createWorkflow($workflow);
    $workflow = $this->testee->getWorkflow($workflowId);
    if (! $workflow) {
      throw new \RuntimeException("Automation not stored.");
    }
    return $workflow;
  }

  public function _after() {
    global $wpdb;
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflows';
    $wpdb->query($sql);
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflow_versions';
    $wpdb->query($sql);
  }
}
