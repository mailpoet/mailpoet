<?php

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Storage\WorkflowRunStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStatisticsStorage;
use MailPoet\Automation\Engine\Storage\WorkflowStorage;

class WorkflowStatisticsStorageTest extends \MailPoetTest
{

  /** @var WorkflowStorage */
  private $workflowStorage;

  /** @var WorkflowRunStorage */
  private $workflowRunStorage;

  /** @var WorkflowStatisticsStorage */
  private $testee;

  /** @var int[] */
  private $workflows = [];

  public function _before() {
    $this->workflowStorage = $this->diContainer->get(WorkflowStorage::class);
    $this->workflowRunStorage = $this->diContainer->get(WorkflowRunStorage::class);
    $this->testee = $this->diContainer->get(WorkflowStatisticsStorage::class);

    $this->workflows = [
      $this->workflowStorage->createWorkflow(
      new Workflow('1', ['root' => new Step('root', Step::TYPE_ROOT, 'root', [], [])],new \WP_User(1))
    ),
      $this->workflowStorage->createWorkflow(
      new Workflow('2', ['root' => new Step('root', Step::TYPE_ROOT, 'root', [], [])],new \WP_User(1))
    ),
      $this->workflowStorage->createWorkflow(
      new Workflow('3', ['root' => new Step('root', Step::TYPE_ROOT, 'root', [], [])],new \WP_User(1))
    ),
    ];
  }

  /**
   * @dataProvider dataForTestItCalculatesTotalsCorrectly
   */
  public function testItCalculatesTotalsCorrectlyForSingleWorkflow(int $workflowIndex, int $expectedTotal, int $expectedInProgress, int $expectedExited, int $versionId = null) {
    $workflow = $this->workflowStorage->getWorkflow($this->workflows[$workflowIndex], $versionId);
    assert($workflow instanceof Workflow);
    $i = 0;
    while($i < $expectedInProgress) {
      $this->createRun($workflow, WorkflowRun::STATUS_RUNNING);
      $i++;
    }
    $i = 0;
    while($i < $expectedExited) {
      $this->createRun($workflow, WorkflowRun::STATUS_FAILED);
      $i++;
    }

    $statistics = $this->testee->getWorkflowStats($workflow->getId(), $versionId);
    $this->assertEquals($expectedInProgress, $statistics->getInProgress());
    $this->assertEquals($expectedTotal, $statistics->getEntered());
    $this->assertEquals($expectedExited, $statistics->getExited());
    $this->assertEquals((bool)$expectedTotal, $statistics->hasValues());
    $this->assertEquals([
      'workflow_id' => $workflow->getId(),
      'has_values' => (bool) $expectedTotal,
      'totals' => [
        'entered' => $expectedTotal,
        'in_progress' => $expectedInProgress,
        'exited' => $expectedExited,
      ],
    ], $statistics->toArray());
  }

  public function dataForTestItCalculatesTotalsCorrectly() {
    return [
      'zero' => [
        1, 0,0,0, null
      ],
      'two-one-one' => [
        0, 2,1,1, null
      ],
      'two-two-zero' => [
        2, 2,2,0, null
      ],
      'two-zero-two' => [
        1, 2,0,2, null
      ],
    ];
  }

  public function testItSeperatesWorkflowRunsCorrectly() {
    $workflow1 = $this->workflowStorage->getWorkflow($this->workflows[0]);
    assert($workflow1 instanceof Workflow);
    $workflow2 = $this->workflowStorage->getWorkflow($this->workflows[1]);
    assert($workflow2 instanceof Workflow);
    $workflow3 = $this->workflowStorage->getWorkflow($this->workflows[2]);
    assert($workflow3 instanceof Workflow);

    $this->createRun($workflow1, WorkflowRun::STATUS_RUNNING);
    $this->createRun($workflow1, WorkflowRun::STATUS_COMPLETE);

    $this->createRun($workflow2, WorkflowRun::STATUS_RUNNING);
    $this->createRun($workflow2, WorkflowRun::STATUS_RUNNING);
    $this->createRun($workflow2, WorkflowRun::STATUS_CANCELLED);
    $this->createRun($workflow2, WorkflowRun::STATUS_CANCELLED);

    $this->createRun($workflow3, WorkflowRun::STATUS_RUNNING);
    $this->createRun($workflow3, WorkflowRun::STATUS_RUNNING);
    $this->createRun($workflow3, WorkflowRun::STATUS_RUNNING);
    $this->createRun($workflow3, WorkflowRun::STATUS_FAILED);
    $this->createRun($workflow3, WorkflowRun::STATUS_FAILED);
    $this->createRun($workflow3, WorkflowRun::STATUS_FAILED);

    $statistics1 = $this->testee->getWorkflowStats($workflow1->getId(), $workflow1->getVersionId());
    $this->assertEquals(1, $statistics1->getInProgress());
    $this->assertEquals(1, $statistics1->getExited());

    $statistics2 = $this->testee->getWorkflowStats($workflow2->getId(), $workflow2->getVersionId());
    $this->assertEquals(2, $statistics2->getInProgress());
    $this->assertEquals(2, $statistics2->getExited());

    $statistics3 = $this->testee->getWorkflowStats($workflow3->getId(), $workflow3->getVersionId());
    $this->assertEquals(3, $statistics3->getInProgress());
    $this->assertEquals(3, $statistics3->getExited());
  }

  public function testItCanDistinguishBetweenVersions() {
    $oldestWorkflow = $this->workflowStorage->getWorkflow($this->workflows[0]);
    assert($oldestWorkflow instanceof Workflow);
    $oldestWorkflow->setName('new-name');
    $this->workflowStorage->updateWorkflow($oldestWorkflow);

    $middleWorkeflow = $this->workflowStorage->getWorkflow($this->workflows[0]);
    assert($middleWorkeflow instanceof Workflow);
    $middleWorkeflow->setName('another-name');
    $this->workflowStorage->updateWorkflow($middleWorkeflow);

    $newestWorkflow = $this->workflowStorage->getWorkflow($this->workflows[0]);
    assert($newestWorkflow instanceof Workflow);
    // 1 Run in the oldest Workflow
    $this->createRun($oldestWorkflow, WorkflowRun::STATUS_CANCELLED);

    // 2 Runs in the middle Workflow
    $this->createRun($middleWorkeflow, WorkflowRun::STATUS_RUNNING);
    $this->createRun($middleWorkeflow, WorkflowRun::STATUS_FAILED);

    // 3 Runs in the newest Workflow
    $this->createRun($newestWorkflow, WorkflowRun::STATUS_RUNNING);
    $this->createRun($newestWorkflow, WorkflowRun::STATUS_RUNNING);
    $this->createRun($newestWorkflow, WorkflowRun::STATUS_RUNNING);

    $stats = $this->testee->getWorkflowStats($newestWorkflow->getId(), null);
    $this->assertEquals(6, $stats->getEntered());

    $stats = $this->testee->getWorkflowStats($newestWorkflow->getId(), $newestWorkflow->getVersionId());
    $this->assertEquals(3, $stats->getEntered());

    $stats = $this->testee->getWorkflowStats($newestWorkflow->getId(), $middleWorkeflow->getVersionId());
    $this->assertEquals(2, $stats->getEntered());

    $stats = $this->testee->getWorkflowStats($newestWorkflow->getId(), $oldestWorkflow->getVersionId());
    $this->assertEquals(1, $stats->getEntered());
  }

  public function _after() {
    global $wpdb;
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflows';
    $wpdb->query($sql);
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflow_versions';
    $wpdb->query($sql);
    $sql = 'truncate ' . $wpdb->prefix . 'mailpoet_workflow_runs';
    $wpdb->query($sql);
  }

  private function createRun(Workflow $workflow, string $status) {
    $run = WorkflowRun::fromArray([
      'workflow_id' => $workflow->getId(),
      'version_id' => $workflow->getVersionId(),
      'trigger_key' => '',
      'subjects' => "[]",
      'id' => 0,
      'status' => $status,
      'created_at' => (new \DateTimeImmutable())->format(\DateTimeImmutable::W3C),
      'updated_at' => (new \DateTimeImmutable())->format(\DateTimeImmutable::W3C),
    ]);
    $this->workflowRunStorage->createWorkflowRun($run);
  }
}
