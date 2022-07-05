<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Control\SubjectLoader;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use wpdb;

class WorkflowRunStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  /** @var SubjectLoader */
  private $subjectLoader;

  public function __construct(
    SubjectLoader $subjectLoader
  ) {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_workflow_runs';
    $this->wpdb = $wpdb;
    $this->subjectLoader = $subjectLoader;
  }

  public function createWorkflowRun(WorkflowRun $workflowRun): int {
    $result = $this->wpdb->insert($this->table, $workflowRun->toArray());
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
    return $this->wpdb->insert_id;
  }

  public function getWorkflowRun(int $id): ?WorkflowRun {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);
    $result = $this->wpdb->get_row($query, ARRAY_A);

    if ($result) {
      $data = (array)$result;
      $subjects = [];
      foreach (Json::decode($data['subjects']) as $key => $args) {
        $subjects[$key] = $this->subjectLoader->loadSubject($key, $args);
      }
      $data['subjects'] = $subjects;
      return WorkflowRun::fromArray($data);
    }
    return null;
  }

  public function updateStatus(int $id, string $status): void {
    $table = esc_sql($this->table);
    $query = (string)$this->wpdb->prepare("UPDATE $table SET status = %s WHERE id = %d", $status, $id);
    $result = $this->wpdb->query($query);
    if ($result === false) {
      throw Exceptions::databaseError($this->wpdb->last_error);
    }
  }
}
