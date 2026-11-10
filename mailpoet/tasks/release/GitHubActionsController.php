<?php declare(strict_types = 1);

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

/**
 * Takes the release ZIP (and, for the free plugin, mailpoet.pot) from GitHub Actions:
 * the zip artifact of a successful .github/workflows/checks.yml run for the latest commit
 * on the release branch of mailpoet/mailpoet or mailpoet/mailpoet-premium.
 */
class GitHubActionsController {
  const RELEASE_BRANCH = 'release';
  const WORKFLOW = 'checks.yml';
  const POT_FILENAME = 'lang/mailpoet.pot';

  // Per project: the repository, the artifact checks.yml uploads, and the ZIP inside it.
  private const PROJECTS = [
    GitHubController::PROJECT_MAILPOET => ['mailpoet', 'mailpoet-zip', GitHubController::FREE_ZIP_FILENAME],
    GitHubController::PROJECT_PREMIUM => ['mailpoet-premium', 'mailpoet-premium-zip', GitHubController::PREMIUM_ZIP_FILENAME],
  ];

  // The checks with the integration and acceptance tests take about half an hour.
  const RERUN_WAIT_TIMEOUT_SECONDS = 2700;
  const RERUN_POLL_INTERVAL_SECONDS = 30;

  private const API_BASE_URI = 'https://api.github.com/repos/mailpoet/';

  /** @var Client */
  private $httpClient;

  /** @var GitHubController */
  private $githubController;

  /** @var int|null */
  private $downloadedRunId;

  /** @var string */
  private $repository;

  /** @var string */
  private $artifactName;

  /** @var string */
  private $zipFilename;

  /**
   * @param GitHubController $githubController for the same project, to find the release commit
   */
  public function __construct(
    string $token,
    GitHubController $githubController,
    ?Client $httpClient = null,
    string $project = GitHubController::PROJECT_MAILPOET
  ) {
    if (!isset(self::PROJECTS[$project])) {
      throw new \Exception("Unsupported project '$project'");
    }
    [$this->repository, $this->artifactName, $this->zipFilename] = self::PROJECTS[$project];
    $this->githubController = $githubController;
    $this->httpClient = $httpClient ?? new Client([
      'headers' => [
        'Accept' => 'application/vnd.github+json',
        'Authorization' => 'Bearer ' . $token,
      ],
    ]);
  }

  /**
   * Downloads the release ZIP to $targetPath. The run it came from is available via
   * getDownloadedRunId(), so mailpoet.pot can be taken from the same build.
   */
  public function downloadLatestBuild(string $targetPath): string {
    $run = $this->getSuccessfulReleaseRun();
    $this->extractFromArtifact((int)$run['id'], $this->zipFilename, $targetPath, false);
    $this->downloadedRunId = (int)$run['id'];
    return $targetPath;
  }

  public function getDownloadedRunId(): ?int {
    return $this->downloadedRunId;
  }

  public function downloadPotFile(int $runId, string $targetPath): void {
    $this->extractFromArtifact($runId, self::POT_FILENAME, $targetPath, true);
  }

  /**
   * Returns true when the latest run on the release branch was started again and
   * false when it had passed.
   */
  public function rerunLatestWorkflow(): bool {
    $response = $this->httpClient->get($this->url('actions/workflows/' . self::WORKFLOW . '/runs'), [
      'query' => ['branch' => self::RELEASE_BRANCH, 'event' => 'push', 'per_page' => 1],
    ]);
    $runs = $this->decode($response)['workflow_runs'] ?? [];
    $run = reset($runs);
    if (!is_array($run) || !isset($run['id'])) {
      throw new \Exception('No ' . self::WORKFLOW . ' run found on the ' . self::RELEASE_BRANCH . ' branch');
    }
    if (($run['status'] ?? null) === 'completed' && ($run['conclusion'] ?? null) === 'success') {
      return false;
    }
    $runId = (int)$run['id'];
    if (($run['status'] ?? null) !== 'completed') {
      // A run can only be started again once it has finished.
      $this->httpClient->post($this->url("actions/runs/$runId/cancel"));
      $this->waitForRunToComplete($runId);
    }
    $this->httpClient->post($this->url("actions/runs/$runId/rerun"));
    return true;
  }

  private function getSuccessfulReleaseRun(): array {
    $revision = $this->githubController->getLatestCommitRevisionOnBranch(self::RELEASE_BRANCH);
    if ($revision === null) {
      throw new \Exception("Couldn't find a GitHub revision for " . self::RELEASE_BRANCH . '. Does the branch exist?');
    }
    // Only the push run builds the release branch itself. The release pull request’s run
    // has the same head commit but builds it merged with the current trunk.
    $response = $this->httpClient->get($this->url('actions/workflows/' . self::WORKFLOW . '/runs'), [
      'query' => ['head_sha' => $revision, 'event' => 'push', 'branch' => self::RELEASE_BRANCH, 'per_page' => 100],
    ]);
    $runs = $this->decode($response)['workflow_runs'] ?? [];
    if (empty($runs)) {
      throw new \Exception('No ' . self::WORKFLOW . " run found for the latest release commit $revision");
    }
    foreach ($runs as $run) {
      if (($run['status'] ?? null) === 'completed' && ($run['conclusion'] ?? null) === 'success') {
        return $run;
      }
    }
    $latest = reset($runs);
    $state = ($latest['status'] ?? null) === 'completed' ? ($latest['conclusion'] ?? 'unknown') : ($latest['status'] ?? 'unknown');
    // A run passes only when all its jobs passed, the release zip and the tests among them.
    throw new \Exception(
      'The release ZIP is only taken from a ' . self::WORKFLOW . " run where every job passed. The latest run for $revision is '$state': "
      . ($latest['html_url'] ?? '')
    );
  }

  /**
   * Writes one file from the run's zip artifact to $targetPath. It goes through
   * temporary files, so a failed download never leaves a partial file at $targetPath.
   */
  private function extractFromArtifact(int $runId, string $entry, string $targetPath, bool $requireContent): void {
    $response = $this->httpClient->get($this->url("actions/runs/$runId/artifacts"), [
      'query' => ['name' => $this->artifactName],
    ]);
    $artifacts = $this->decode($response)['artifacts'] ?? [];
    $artifact = reset($artifacts);
    if (!is_array($artifact) || !isset($artifact['id'])) {
      throw new \Exception("No '" . $this->artifactName . "' artifact found in run $runId");
    }
    if (!empty($artifact['expired'])) {
      throw new \Exception(
        "The '" . $this->artifactName . "' artifact of run $runId has expired. Run it again to build a new one: "
        . "`gh run rerun $runId -R mailpoet/{$this->repository}`, or https://github.com/mailpoet/{$this->repository}/actions/runs/$runId"
      );
    }

    $artifactPath = $targetPath . '.' . uniqid('', true) . '.artifact.zip';
    $tempPath = $targetPath . '.' . uniqid('', true) . '.tmp';
    try {
      $this->httpClient->get($this->url('actions/artifacts/' . (int)$artifact['id'] . '/zip'), ['sink' => $artifactPath]);
      $zip = new \ZipArchive();
      if ($zip->open($artifactPath) !== true) {
        throw new \Exception("The '" . $this->artifactName . "' artifact of run $runId could not be opened.");
      }
      $content = $zip->getFromName($entry);
      $zip->close();
      if ($content === false) {
        throw new \Exception("The '" . $this->artifactName . "' artifact of run $runId has no '$entry'.");
      }
      if ($requireContent && $content === '') {
        throw new \Exception("'$entry' in run $runId is empty.");
      }
      if (file_put_contents($tempPath, $content) === false || !rename($tempPath, $targetPath)) {
        throw new \Exception("Couldn't write '$entry' to $targetPath.");
      }
    } finally {
      foreach ([$artifactPath, $tempPath] as $path) {
        if (file_exists($path)) {
          unlink($path);
        }
      }
    }
  }

  private function waitForRunToComplete(int $runId): void {
    $timeout = $this->getRerunWaitTimeoutSeconds();
    $deadline = time() + $timeout;
    while (true) {
      $run = $this->decode($this->httpClient->get($this->url("actions/runs/$runId")));
      $status = $run['status'] ?? null;
      if ($status === 'completed') {
        return;
      }
      if (time() >= $deadline) {
        throw new \Exception(sprintf(
          "Timed out after %ds waiting for run %d to finish (last status: '%s').",
          $timeout,
          $runId,
          $status ?? 'unknown'
        ));
      }
      $this->sleep(self::RERUN_POLL_INTERVAL_SECONDS);
    }
  }

  private function url(string $path): string {
    return self::API_BASE_URI . $this->repository . '/' . $path;
  }

  private function decode($response): array {
    $data = json_decode($response->getBody()->getContents(), true);
    return is_array($data) ? $data : [];
  }

  protected function sleep(int $seconds): void {
    sleep($seconds);
  }

  protected function getRerunWaitTimeoutSeconds(): int {
    return self::RERUN_WAIT_TIMEOUT_SECONDS;
  }
}
