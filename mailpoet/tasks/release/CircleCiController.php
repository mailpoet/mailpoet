<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class CircleCiController {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  const RELEASE_BRANCH = 'release';
  const RELEASE_ZIP_JOB_NAME = 'build_release_zip';
  const JOB_STATUS_SUCCESS = 'success';

  const FREE_ZIP_FILENAME = 'mailpoet.zip';
  const PREMIUM_ZIP_FILENAME = 'mailpoet-premium.zip';

  const TERMINAL_WORKFLOW_STATUSES = ['success', 'failed', 'error', 'canceled', 'unauthorized'];
  const RERUNNABLE_WORKFLOW_STATUSES = ['running', 'failed', 'failing', 'canceled'];
  // The full premium CI workflow runs close to 30 minutes; a 'failing' workflow
  // stays non-terminal until its remaining jobs finish, so allow ample headroom.
  const WORKFLOW_RERUN_WAIT_TIMEOUT_SECONDS = 2700;
  const WORKFLOW_RERUN_POLL_INTERVAL_SECONDS = 30;

  /** @var string */
  private $token;

  /** @var string */
  private $username;

  /** @var string */
  private $zipFilename;

  /** @var Client */
  private $httpClient;

  /** @var GitHubController */
  private $githubController;

  public function __construct(
    $username,
    $token,
    $project,
    GitHubController $githubController,
    ?Client $httpClient = null
  ) {
    $this->username = $username;
    $this->token = $token;
    $circleCiProject = $this->getCircleCiProject($project);
    $this->zipFilename = $project === self::PROJECT_MAILPOET ? self::FREE_ZIP_FILENAME : self::PREMIUM_ZIP_FILENAME;
    $this->httpClient = $httpClient ?? new Client([
      'auth' => [$token, null],
      'headers' => [
        'Accept' => 'application/json',
      ],
      'base_uri' => 'https://circleci.com/api/v2/project/gh/' . urlencode($username) . "/$circleCiProject/",
    ]);
    $this->githubController = $githubController;
  }

  public function downloadLatestBuild($targetPath) {
    $job = $this->getLatestZipBuildJob();
    $this->checkZipBuildJobStatus($job);

    $releaseZipUrl = $this->getReleaseZipUrl($job['job_number']);

    $this->httpClient->get($releaseZipUrl, [
      'sink' => $targetPath,
      'query' => [
        'circle-token' => $this->token, // artifact download requires token as query param
      ],
    ]);
    return $targetPath;
  }

  /**
   * Returns true when the Circle workflow was started from the beginning
   * and false when the last workflow was successful.
   */
  public function rerunLatestWorkflow(?string $project = null): bool {
    $circleCiProject = null;
    // We use the current project if the project parameter is null
    if ($project) {
      $project = strtoupper($project);
      $supportedProjects = [
        \MailPoetTasks\Release\CircleCiController::PROJECT_PREMIUM,
        \MailPoetTasks\Release\CircleCiController::PROJECT_MAILPOET,
      ];
      if (!in_array($project, $supportedProjects, true)) {
        throw new \Exception('Unsupported project');
      }
      $circleCiProject = $this->getCircleCiProject($project);
    }
    $pipeline = $this->getLatestPipeline($circleCiProject);
    if (!$pipeline) {
      throw new \Exception('No pipeline found');
    }
    $workflow = $this->getWorkflowByPipelineId($pipeline['id']);
    if (!$workflow) {
      throw new \Exception('No workflow found');
    }
    $workflowStatus = $workflow['status'] ?? null;

    if (!in_array($workflowStatus, self::RERUNNABLE_WORKFLOW_STATUSES, true)) {
      return false;
    }

    if ($workflowStatus === 'running') {
      // Cancellation is asynchronous; the workflow stays non-terminal for a while.
      $this->cancelWorkflow($workflow['id']);
    }

    $this->waitForWorkflowToReachTerminalState($workflow['id']);
    $this->rerunWorkflow($workflow['id']);
    return true;
  }

  private function getLatestZipBuildJob(): array {
    $latestPipeline = $this->getLatestPipeline();
    $latestPipelineId = $latestPipeline['id'] ?? null;
    $latestPipelineRevision = $latestPipeline['vcs']['revision'] ?? null;

    if ($latestPipelineId === null) {
      throw new \Exception('No release ZIP build found');
    }

    // ensure we're downloading latest revision on given branch
    $latestRevision = $this->githubController->getLatestCommitRevisionOnBranch(self::RELEASE_BRANCH);
    if ($latestRevision === null) {
      throw new \Exception("Couldn't find a Github revision for " . self::RELEASE_BRANCH . ". Does the branch exist?");
    }
    if ($latestPipelineRevision !== $latestRevision) {
      throw new \Exception(
        "Found latest pipeline run from revision '$latestPipelineRevision' but the latest one on Github is '$latestRevision'"
      );
    }

    $latestWorkflow = $this->getWorkflowByPipelineId($latestPipelineId);
    $latestWorkFlowId = $latestWorkflow['id'] ?? null;
    if ($latestWorkFlowId === null) {
      throw new \Exception('No release ZIP build found');
    }

    $responseJob = $this->httpClient->get('https://circleci.com/api/v2/workflow/' . urlencode($latestWorkFlowId) . '/job');
    $jobs = json_decode($responseJob->getBody()->getContents(), true);

    foreach ($jobs['items'] as $job) {
      if ($job['name'] === self::RELEASE_ZIP_JOB_NAME) {
        return $job;
      }
    }
    throw new \Exception('No release ZIP build found');
  }

  private function checkZipBuildJobStatus(array $job) {
    if ($job['status'] !== self::JOB_STATUS_SUCCESS) {
      $expectedStatus = self::JOB_STATUS_SUCCESS;
      throw new \Exception("Job has invalid status '$job[status]', '$expectedStatus' expected");
    }
  }

  private function getReleaseZipUrl($buildNumber) {
    $response = $this->httpClient->get("$buildNumber/artifacts");
    $artifacts = json_decode($response->getBody()->getContents(), true);

    $pattern = preg_quote($this->zipFilename, '~');
    foreach ($artifacts['items'] as $artifact) {
      if (preg_match("~/$pattern$~", $artifact['path'])) {
        return $artifact['url'];
      }
    }
    throw new \Exception('No ZIP file found in build artifacts');
  }

  /**
   * Returns the latest pipeline for the current project or the for the specific when is set in the project argument
   * @param string|null $project
   * @return array|null
   */
  private function getLatestPipeline(?string $project = null): ?array {
    // Latest Pipeline for release branch
    $params = [
      'query' => ['branch' => urlencode(self::RELEASE_BRANCH)],
    ];

    if ($project) {
      $username = urlencode($this->username);
      $circleCiProject = urlencode($project);
      $response = $this->httpClient->get("https://circleci.com/api/v2/project/gh/{$username}/{$circleCiProject}/pipeline", $params);
    } else {
      $response = $this->httpClient->get('pipeline', $params);
    }

    $pipelines = json_decode($response->getBody()->getContents(), true);
    return reset($pipelines['items']) ?: null;
  }

  private function getWorkflowByPipelineId(string $pipelineId): ?array {
    $responseWorkflows = $this->httpClient->get('https://circleci.com/api/v2/pipeline/' . urlencode($pipelineId) . '/workflow');
    $workflows = json_decode($responseWorkflows->getBody()->getContents(), true);
    $workflows = $workflows['items'] ?? [];
    return reset($workflows) ?: null;
  }

  private function getWorkflowById(string $workflowId): ?array {
    $response = $this->httpClient->get('https://circleci.com/api/v2/workflow/' . urlencode($workflowId));
    $workflow = json_decode($response->getBody()->getContents(), true);
    return isset($workflow['id']) ? $workflow : null;
  }

  /**
   * Polls the workflow until it reaches a terminal state so it can be rerun.
   * CircleCI returns 400 ("Workflow must be in a terminal state to be rerun")
   * when rerunning a 'running'/'failing' workflow, so wait it out first.
   */
  private function waitForWorkflowToReachTerminalState(string $workflowId): void {
    $timeout = $this->getRerunWaitTimeoutSeconds();
    $deadline = time() + $timeout;
    while (true) {
      $workflow = $this->getWorkflowById($workflowId);
      $lastStatus = $workflow['status'] ?? null;
      if (in_array($lastStatus, self::TERMINAL_WORKFLOW_STATUSES, true)) {
        return;
      }
      if (time() >= $deadline) {
        throw new \Exception(sprintf(
          "Timed out after %ds waiting for CircleCI workflow '%s' to reach a terminal state (last status: '%s').",
          $timeout,
          $workflowId,
          $lastStatus ?? 'unknown'
        ));
      }
      $this->sleep(self::WORKFLOW_RERUN_POLL_INTERVAL_SECONDS);
    }
  }

  private function rerunWorkflow(string $workflowId, bool $fromFailed = false): void {
    $this->httpClient->post(
      'https://circleci.com/api/v2/workflow/' . urlencode($workflowId) . '/rerun',
      [
        'json' => [
          'from_failed' => $fromFailed,
        ],
      ]
    );
  }

  private function cancelWorkflow(string $workflowId): void {
    $this->httpClient->post('https://circleci.com/api/v2/workflow/' . urlencode($workflowId) . '/cancel');
  }

  private function getCircleCiProject(string $project): string {
    return $project === self::PROJECT_MAILPOET ? 'mailpoet' : 'mailpoet-premium';
  }

  protected function sleep(int $seconds): void {
    sleep($seconds);
  }

  protected function getRerunWaitTimeoutSeconds(): int {
    return self::WORKFLOW_RERUN_WAIT_TIMEOUT_SECONDS;
  }
}
