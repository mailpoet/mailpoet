<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

class ReleaseVersionController {

  /** @var JiraController */
  private $jira;

  /** @var GitHubController */
  private $github;

  /** @var string */
  private $project;

  public function __construct(
    JiraController $jira,
    GitHubController $github,
    $project
  ) {
    $this->jira = $jira;
    $this->github = $github;
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

    $partToIncrement = VersionHelper::MINOR;

    if ($this->project === JiraController::PROJECT_MAILPOET) {
      $isPremiumReleased = $this->github->projectBranchExists(
        JiraController::PROJECT_PREMIUM,
        GitHubController::RELEASE_SOURCE_BRANCH
      );

      if (!$isPremiumReleased) {
        $partToIncrement = VersionHelper::PATCH;
      }
    } elseif ($this->project === JiraController::PROJECT_PREMIUM) {
      $lastVersion = $this->jira->getLastReleasedVersion(JiraController::PROJECT_MAILPOET);
    }

    $nextVersion = VersionHelper::incrementVersion($lastVersion['name'], $partToIncrement);
    return $nextVersion;
  }

  public function getPreparedVersion(): string {
    $version = $this->jira->getPreparedReleaseVersion();
    return $version['name'];
  }

  private function getUnreleasedDoneIssues($project = null) {
    $project = $project ?: $this->project;
    $jql = "project = $project AND status = Done AND (fixVersion = EMPTY OR fixVersion IN unreleasedVersions()) AND updated >= -52w";
    $result = $this->jira->search($jql);
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
