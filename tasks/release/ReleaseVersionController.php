<?php

namespace MailPoetTasks\Release;

require_once __DIR__ . '/Jira.php';

class ReleaseVersionController {

  /** @var Jira */
  private $jira;

  /** @var string */
  private $project;

  function __construct(Jira $jira, $project) {
    $this->jira = $jira;
    $this->project = $project;
  }

  static function createWithJiraCredentials($token, $user, $project) {
    return new self(new Jira($token, $user, $project), $project);
  }

  function assignVersionToCompletedTickets($version) {
    $output = [];
    $output[] = "Checking version $version in $this->project";

    if (!$this->checkVersion($version)) {
      $output[] = "The version is invalid or already released";
      return join("\n", $output);
    }

    $output[] = "Setting version $version to completed tickets in $this->project...";
    $issues = $this->getDoneIssuesWithoutVersion();
    $result = array_map(function ($issue) use ($version) {
      return $this->setIssueFixVersion($issue['key'], $version);
    }, $issues);
    $output[] = "Done, issues processed: " . count($result);

    return join("\n", $output);
  }

  function getDoneIssuesWithoutVersion() {
    $jql = "project = $this->project AND status = Done AND (fixVersion = EMPTY OR fixVersion IN unreleasedVersions()) AND updated >= -52w";
    $result = $this->jira->search($jql, ['key']);
    return array_map(function ($issue) {
      return [
        'id' => $issue['id'],
        'key' => $issue['key'],
      ];
    }, $result['issues']);
  }

  function checkVersion($version) {
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
    }
    // version exists
    return true;
  }

  function setIssueFixVersion($issue_key, $version) {
    $data = [
      'update' => [
        'fixVersions' => [
          ['set' => [['name' => $version]]]
        ]
      ]
    ];
    return $this->jira->updateIssue($issue_key, $data);
  }
}
