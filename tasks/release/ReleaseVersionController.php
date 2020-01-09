<?php

namespace MailPoetTasks\Release;

class ReleaseVersionController {

  /** @var JiraController */
  private $jira;

  /** @var string */
  private $project;

  public function __construct(JiraController $jira, $project) {
    $this->jira = $jira;
    $this->project = $project;
  }

  public function assignVersionToCompletedTickets($version = null) {
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

  public function determineNextVersion() {
    $lastVersion = $this->jira->getLastReleasedVersion();

    $partToIncrement = VersionHelper::PATCH;

    if ($this->project === JiraController::PROJECT_MAILPOET) {
      $freeIncrement = $this->checkProjectVersionIncrement(JiraController::PROJECT_MAILPOET);
      $premiumIncrement = $this->checkProjectVersionIncrement(JiraController::PROJECT_PREMIUM);

      if (in_array(VersionHelper::MINOR, [$freeIncrement, $premiumIncrement])) {
        $partToIncrement = VersionHelper::MINOR;
      }
    }

    $nextVersion = VersionHelper::incrementVersion($lastVersion['name'], $partToIncrement);
    return $nextVersion;
  }

  private function checkProjectVersionIncrement($project) {
    $issues = $this->getUnreleasedDoneIssues($project);

    $partToIncrement = VersionHelper::PATCH;
    $fieldId = JiraController::VERSION_INCREMENT_FIELD_ID;

    foreach ($issues as $issue) {
      if (!empty($issue['fields'][$fieldId]['value'])
        && $issue['fields'][$fieldId]['value'] === VersionHelper::MINOR
      ) {
        $partToIncrement = VersionHelper::MINOR;
        break;
      }
    }

    return $partToIncrement;
  }

  private function getUnreleasedDoneIssues($project = null) {
    $project = $project ?: $this->project;
    $jql = "project = $project AND status = Done AND (fixVersion = EMPTY OR fixVersion IN unreleasedVersions()) AND updated >= -52w";
    $result = $this->jira->search($jql, ['key', JiraController::VERSION_INCREMENT_FIELD_ID]);
    return $result['issues'];
  }

  private function ensureCorrectVersion($version) {
    try {
      $versionData = $this->jira->getVersion($version);
    } catch (\Exception $e) {
      $versionData = false;
    }
    if (!empty($versionData['released'])) {
      // version is already released
      return false;
    } else if (empty($versionData)) {
      // version does not exist
      $this->jira->createVersion($version);
      return $version;
    }
    // version exists
    return $versionData['name'];
  }

  private function setIssueFixVersion($issueKey, $version) {
    $data = [
      'update' => [
        'fixVersions' => [
          ['set' => [['name' => $version]]],
        ],
      ],
    ];
    return $this->jira->updateIssue($issueKey, $data);
  }
}
