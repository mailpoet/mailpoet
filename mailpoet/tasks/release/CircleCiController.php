<?php

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
    GitHubController $githubController
  ) {
    $this->username = $username;
    $this->token = $token;
    $circleCiProject = $this->getCircleCiProject($project);
    $this->zipFilename = $project === self::PROJECT_MAILPOET ? self::FREE_ZIP_FILENAME : self::PREMIUM_ZIP_FILENAME;
    $this->httpClient = new Client([
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
      'save_to' => $targetPath,
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
  public function rerunLatestWorkflow(string $project): bool {
    $circleCiProject = $this->getCircleCiProject($project);
    $pipeline = $this->getLatestPipeline($circleCiProject);
    if (!$pipeline) {
      throw new \Exception('No pipeline found');
    }
    $workflow = $this->getWorkflowByPipelineId($pipeline['id']);
    if (!$workflow) {
      throw new \Exception('No workflow found');
    }
    $workflowStatus = $workflow['status'] ?? null;

    if (in_array($workflowStatus, ['running', 'failed', 'failing', 'canceled'], true)) {
      if ($workflowStatus === 'running') {
        $this->cancelWorkflow($workflow['id']);
      }
      $this->rerunWorkflow($workflow['id']);
      return true;
    }
    return false;
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
}
