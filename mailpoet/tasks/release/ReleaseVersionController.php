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

  public function getLatestVersion() {
    return $this->github->getLastReleasedVersion();
  }

  public function determineNextVersion() {
    $lastVersion = $this->getLatestVersion();

    $partToIncrement = VersionHelper::MINOR;

    if ($this->project === JiraController::PROJECT_MAILPOET) {
      $isPremiumReleased = $this->github->projectBranchExists(
        JiraController::PROJECT_PREMIUM,
        GitHubController::RELEASE_SOURCE_BRANCH
      );

      if (!$isPremiumReleased) {
        $partToIncrement = VersionHelper::PATCH;
      }
    }

    $nextVersion = VersionHelper::incrementVersion($lastVersion, $partToIncrement);
    return $nextVersion;
  }
}
