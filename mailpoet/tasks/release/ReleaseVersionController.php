<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

class ReleaseVersionController {

  /** @var GitHubController */
  private $github;

  /** @var string */
  private $project;

  public function __construct(
    GitHubController $github,
    $project
  ) {
    $this->github = $github;
    $this->project = $project;
  }

  public function getLatestVersion() {
    return $this->github->getLastReleasedVersion();
  }

  public function determineNextVersion() {
    $lastVersion = $this->getLatestVersion();

    $partToIncrement = VersionHelper::MINOR;

    if ($this->project === GitHubController::PROJECT_MAILPOET) {
      $isPremiumReleased = $this->github->projectBranchExists(
        GitHubController::PROJECT_PREMIUM,
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
