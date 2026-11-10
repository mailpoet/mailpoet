<?php declare(strict_types = 1);

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

/**
 * Takes the release ZIP and mailpoet.pot of the free plugin from GitHub Actions:
 * the mailpoet-zip artifact of a successful .github/workflows/checks.yml run for the
 * latest commit on the release branch.
 */
class GitHubActionsController {
  const RELEASE_BRANCH = 'release';
  const WORKFLOW = 'checks.yml';
  const ARTIFACT_NAME = 'mailpoet-zip';
  const ZIP_FILENAME = 'mailpoet.zip';
  const POT_FILENAME = 'lang/mailpoet.pot';

  // The checks with the integration and acceptance tests take about half an hour.
  const RERUN_WAIT_TIMEOUT_SECONDS = 2700;
  const RERUN_POLL_INTERVAL_SECONDS = 30;

  private const API_BASE_URI = 'https://api.github.com/repos/mailpoet/mailpoet/';

  /** @var Client */
  private $httpClient;

  /** @var GitHubController */
  private $githubController;

  /** @var int|null */
  private $downloadedRunId;

  public function __construct(
    string $token,
    GitHubController $githubController,
    ?Client $httpClient = null
  ) {
    $this->githubController = $githubController;
    $this->httpClient = $httpClient ?? new Client([
      'headers' => [
        'Accept' => 'application/vnd.github+json',
        'Authorization' => 'Bearer ' . $token,
      ],
      'base_uri' => self::API_BASE_URI,
    ]);
  }

  /**
   * Downloads the release ZIP to $targetPath. The run it came from is available via
   * getDownloadedRunId(), so mailpoet.pot can be taken from the same build.
   */
  public function downloadLatestBuild(string $targetPath): string {
    $run = $this->getSuccessfulReleaseRun();
    $this->extractFromArtifact((int)$run['id'], self::ZIP_FILENAME, $targetPath, false);
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
    $response = $this->httpClient->get('actions/workflows/' . self::WORKFLOW . '/runs', [
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
      $this->httpClient->post("actions/runs/$runId/cancel");
      $this->waitForRunToComplete($runId);
    }
    $this->httpClient->post("actions/runs/$runId/rerun");
    return true;
  }

  private function getSuccessfulReleaseRun(): array {
    $revision = $this->githubController->getLatestCommitRevisionOnBranch(self::RELEASE_BRANCH);
    if ($revision === null) {
      throw new \Exception("Couldn't find a GitHub revision for " . self::RELEASE_BRANCH . '. Does the branch exist?');
    }
    $response = $this->httpClient->get('actions/workflows/' . self::WORKFLOW . '/runs', [
      'query' => ['head_sha' => $revision, 'per_page' => 100],
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
    // A run passes only when all its jobs passed: the tests, the release zip, its PHP 7.4 lint and the QIT scan.
    throw new \Exception(
      'The release ZIP is only taken from a ' . self::WORKFLOW . " run where every job passed. The latest run for $revision is '$state': "
      . ($latest['html_url'] ?? '')
    );
  }

  /**
   * Writes one file from the run's mailpoet-zip artifact to $targetPath. It goes through
   * temporary files, so a failed download never leaves a partial file at $targetPath.
   */
  private function extractFromArtifact(int $runId, string $entry, string $targetPath, bool $requireContent): void {
    $response = $this->httpClient->get("actions/runs/$runId/artifacts", [
      'query' => ['name' => self::ARTIFACT_NAME],
    ]);
    $artifacts = $this->decode($response)['artifacts'] ?? [];
    $artifact = reset($artifacts);
    if (!is_array($artifact) || !isset($artifact['id'])) {
      throw new \Exception("No '" . self::ARTIFACT_NAME . "' artifact found in run $runId");
    }
    if (!empty($artifact['expired'])) {
      throw new \Exception("The '" . self::ARTIFACT_NAME . "' artifact of run $runId has expired. Re-run the checks on the release branch.");
    }

    $artifactPath = $targetPath . '.' . uniqid('', true) . '.artifact.zip';
    $tempPath = $targetPath . '.' . uniqid('', true) . '.tmp';
    try {
      $this->httpClient->get('actions/artifacts/' . (int)$artifact['id'] . '/zip', ['sink' => $artifactPath]);
      $zip = new \ZipArchive();
      if ($zip->open($artifactPath) !== true) {
        throw new \Exception("The '" . self::ARTIFACT_NAME . "' artifact of run $runId could not be opened.");
      }
      $content = $zip->getFromName($entry);
      $zip->close();
      if ($content === false) {
        throw new \Exception("The '" . self::ARTIFACT_NAME . "' artifact of run $runId has no '$entry'.");
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
      $run = $this->decode($this->httpClient->get("actions/runs/$runId"));
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
