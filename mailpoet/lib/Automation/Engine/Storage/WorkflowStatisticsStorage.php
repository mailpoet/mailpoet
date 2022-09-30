<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Data\WorkflowStatistics;

class WorkflowStatisticsStorage {

  /** @var string  */
  private $table;

  /** @var \wpdb  */
  private $wpdb;

  public function __construct() {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_workflow_runs';
    $this->wpdb = $wpdb;
  }

  public function getWorkflowStats(int $workflowId, int $versionId = null): WorkflowStatistics {
    return new WorkflowStatistics(
      $workflowId,
      $this->getTotalRuns($workflowId, $versionId),
      $this->getRunsWithStatuts(WorkflowRun::STATUS_RUNNING, $workflowId, $versionId),
      $versionId
    );
  }

  private function getTotalRuns(int $workflowId, int $versionId = null): int {
    $table = esc_sql($this->table);

    /** @var string $sql */
    $sql = !$versionId ?
      $this->wpdb->prepare("SELECT count(ID) AS total_runs FROM $table WHERE workflow_id=%d", $workflowId) :
      $this->wpdb->prepare("SELECT count(ID) AS total_runs FROM $table WHERE workflow_id=%d AND version_id=%d", $workflowId, $versionId);

    $result = $this->wpdb->get_col($sql);
    return $result[0] ? (int)$result[0] : 0;

  }

  private function getRunsWithStatuts(string $status, int $workflowId, int $versionId = null): int {
    $table = esc_sql($this->table);

    /** @var string $sql */
    $sql = !$versionId ?
      $this->wpdb->prepare("SELECT count(ID) AS total_runs FROM $table WHERE status=%s AND workflow_id=%d", $status, $workflowId) :
      $this->wpdb->prepare("SELECT count(ID) AS total_runs FROM $table WHERE status=%s AND workflow_id=%d and version_id=%d", $status, $workflowId, $versionId);

    $result = $this->wpdb->get_col($sql);
    return $result[0] ? (int)$result[0] : 0;
  }
}
