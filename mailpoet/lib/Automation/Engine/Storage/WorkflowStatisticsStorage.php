<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Workflow;
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

  /**
   * @param Workflow ...$workflows
   * @return WorkflowStatistics[]
   * @throws \MailPoet\Automation\Engine\Exceptions\InvalidStateException
   */
  public function getWorkflowStatisticsForWorkflows(Workflow ...$workflows): array {
    if (empty($workflows)) {
      return [];
    }
    $workflowIds = array_map(
      function(Workflow $workflow): int {
        return $workflow->getId();
      },
      $workflows
    );
    $runMatrix = $this->queryRunsFor(...$workflowIds);
    $statistics = [];
    foreach ($workflows as $workflow) {
      $statistics[$workflow->getId()] = $this->buildWorkflowStatisticFromMatrix($runMatrix, $workflow->getId(), null);
    }
    return $statistics;
  }

  public function getWorkflowStats(int $workflowId, int $versionId = null): WorkflowStatistics {
    return $this->buildWorkflowStatisticFromMatrix(
      $this->queryRunsFor($workflowId),
      $workflowId,
      $versionId
    );
  }

  private function buildWorkflowStatisticFromMatrix(array $matrix, int $workflowId, int $versionId = null): WorkflowStatistics {

    $workflowMatrix = $matrix[$workflowId] ?? [];
    $versionMatrix = $workflowMatrix[$versionId] ?? [];
    if ($versionId === null) {
      foreach ($workflowMatrix as $version) {
        foreach ($version as $status => $runs) {
          if (!isset($versionMatrix[$status])) {
            $versionMatrix[$status] = 0;
          }
          $versionMatrix[$status] += $runs;
        }
      }
    }
    $totals = (int)array_sum($versionMatrix);
    return new WorkflowStatistics(
      $workflowId,
      $totals,
      $versionMatrix[WorkflowRun::STATUS_RUNNING] ?? 0,
      $versionId
    );
  }

  /**
   * Returns an array with all runs for all versions of the given workflows
   * [
   *    <workflow_id> => [
   *      <version_id> => [
   *        <status> => int,
   *      ],
   *   ],
   * ]
   * @param int ...$workflowIds
   * @return array<int,array<int,array<string,int>>>
   */
  private function queryRunsFor(int ...$workflowIds): array {
    $table = esc_sql($this->table);

    $sql = 'SELECT status,workflow_id as workfowId,version_id as versionId,count(id) as runs FROM ' . $table . ' where workflow_id IN (' . implode(',', $workflowIds) . ') group by status,workflow_id,version_id; ';

    $matrix = [];
    // All parameters are either escaped or type safe.
    // phpcs:ignore WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter
    $results = $this->wpdb->get_results($sql);
    if (!is_array($results) || !count($results)) {
      return $matrix;
    }
    foreach ($results as $result) {
      $matrix[(int)$result->workfowId][(int)$result->versionId][(string)$result->status] = (int)$result->runs;
    }
    return $matrix;
  }
}
