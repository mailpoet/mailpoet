<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use wpdb;

class WorkflowRunStorage {
  /** @var string */
  private $table;

  /** @var wpdb */
  private $wpdb;

  /** @var Registry  */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    global $wpdb;
    $this->table = $wpdb->prefix . 'mailpoet_automation_workflow_runs';
    $this->wpdb = $wpdb;
    $this->registry = $registry;
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
    $data = $this->wpdb->get_row($query, ARRAY_A);
    if (!is_array($data) || empty($data)) {
      return null;
    }
    $data['subjects'] = isset($data['subjects']) ? $this->createSubjects(Json::decode($data['subjects'])) : [];
    return WorkflowRun::fromArray($data);
  }

  /**
   * @param array $rawSubjects
   * @return Subject[]
   */
  private function createSubjects(array $rawSubjects): array {
    $subjects = [];
    $subjectFactories = $this->registry->getSubjectFactories();
    foreach ($rawSubjects as $subjectKey => $subjectData) {
      foreach ($subjectFactories as $factory) {
        if (!$factory->canHandle($subjectKey)) {
          continue;
        }
        try {
          $subject = $factory->forKey($subjectKey);
          $subject->load($subjectData);
          $subjects[] = $subject;
        } catch (Exceptions\UnexpectedSubjectType $error) {
          continue;
        }
        break;
      }
    }
    return $subjects;
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
