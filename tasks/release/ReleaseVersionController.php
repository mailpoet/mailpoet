<?php

namespace MailPoetTasks\Release;

class ReleaseVersionController {

  /** @var JiraController */
  private $jira;

  /** @var string */
  private $project;

  function __construct(JiraController $jira, $project) {
    $this->jira = $jira;
    $this->project = $project;
  }

  function assignVersionToCompletedTickets($version = null) {
    $version = $this->ensureCorrectVersion($version);
    if (!$version) {
      throw new \Exception('The version is invalid or already released');
    }

    $output = [];
    $output[] = "Setting version $version to completed tickets in $this->project...";
    $issues = $this->getUnreleasedDoneIssues();
    $result = array_map(function ($issue) use ($version) {
      return $this->setIssueFixVersion($issue['key'], $version);
    }, $issues);
    $output[] = "Done, issues processed: " . count($result);

    return [$version, join("\n", $output)];
  }

  function determineNextVersion() {
    $last_version = $this->jira->getLastReleasedVersion();

    $part_to_increment = VersionHelper::PATCH;

    if ($this->project === JiraController::PROJECT_MAILPOET) {
      $free_increment = $this->checkProjectVersionIncrement(JiraController::PROJECT_MAILPOET);
      $premium_increment = $this->checkProjectVersionIncrement(JiraController::PROJECT_PREMIUM);

      if (in_array(VersionHelper::MINOR, [$free_increment, $premium_increment])) {
        $part_to_increment = VersionHelper::MINOR;
      }
    }

    $next_version = VersionHelper::incrementVersion($last_version['name'], $part_to_increment);
    return $next_version;
  }

  private function checkProjectVersionIncrement($project) {
    $issues = $this->getUnreleasedDoneIssues($project);

    $part_to_increment = VersionHelper::PATCH;
    $field_id = JiraController::VERSION_INCREMENT_FIELD_ID;

    foreach ($issues as $issue) {
      if (!empty($issue['fields'][$field_id]['value'])
        && $issue['fields'][$field_id]['value'] === VersionHelper::MINOR
      ) {
        $part_to_increment = VersionHelper::MINOR;
        break;
      }
    }

    return $part_to_increment;
  }

  private function getUnreleasedDoneIssues($project = null) {
    $project = $project ?: $this->project;
    $jql = "project = $project AND status = Done AND (fixVersion = EMPTY OR fixVersion IN unreleasedVersions()) AND updated >= -52w";
    $result = $this->jira->search($jql, ['key', JiraController::VERSION_INCREMENT_FIELD_ID]);
    return $result['issues'];
  }

  private function ensureCorrectVersion($version) {
    try {
      $version_data = $this->jira->getVersion($version);
    } catch (\Exception $e) {
      $version_data = false;
    }
    if (!empty($version_data['released'])) {
      // version is already released
      return false;
    } else if (empty($version_data)) {
      // version does not exist
      $this->jira->createVersion($version);
      return $version;
    }
    // version exists
    return $version_data['name'];
  }

  private function setIssueFixVersion($issue_key, $version) {
    $data = [
      'update' => [
        'fixVersions' => [
          ['set' => [['name' => $version]]],
        ],
      ],
    ];
    return $this->jira->updateIssue($issue_key, $data);
  }
}
