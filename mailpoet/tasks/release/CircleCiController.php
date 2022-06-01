<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class CircleCiController {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  const MAIN_BRANCH = 'trunk';
  const RELEASE_BRANCH = 'release';
  const RELEASE_ZIP_JOB_NAME = 'build_release_zip';
  const JOB_STATUS_SUCCESS = 'success';

  const FREE_ZIP_FILENAME = 'mailpoet.zip';
  const PREMIUM_ZIP_FILENAME = 'mailpoet-premium.zip';

  /** @var string */
  private $token;

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
    $this->token = $token;
    $circleCiProject = $project === self::PROJECT_MAILPOET ? 'mailpoet' : 'mailpoet-premium';
    $this->zipFilename = $project === self::PROJECT_MAILPOET ? self::FREE_ZIP_FILENAME : self::PREMIUM_ZIP_FILENAME;
    $this->httpClient = new Client([
      'auth' => [null, $token],
      'headers' => [
        'Accept' => 'application/json',
      ],
      'base_uri' => 'https://circleci.com/api/v2/project/gh/' . urlencode($username) . "/$circleCiProject/",
    ]);
    $this->githubController = $githubController;
  }

  public function downloadLatestBuild($targetPath, $branch = self::RELEASE_BRANCH) {
    $job = $this->getZipBuildJobForBranch($branch);
    $this->checkZipBuildJobStatus($job);
    $this->downloadZipFromJob($job, $targetPath);
    return $targetPath;
  }

  public function downloadParentBuildFromMain(string $targetPath, string $branch): string {
    // Make sure branches are fetched
    trim(shell_exec('git fetch --no-tags origin ' . self::MAIN_BRANCH . ':' . self::MAIN_BRANCH));
    trim(shell_exec("git fetch --no-tags origin $branch:$branch"));
    $parentCommitCmd = 'LA=$(git log trunk..' . $branch . ' --pretty=format:"%h" | tail -1);git rev-parse $LA^';
    $parentCommit = trim(shell_exec($parentCommitCmd));
    $job = $this->getZipBuildJobForBranch(self::MAIN_BRANCH, $parentCommit);
    $this->checkZipBuildJobStatus($job);
    $this->downloadZipFromJob($job, $targetPath);
    return $targetPath;
  }

  private function getZipBuildJobForBranch(string $branch, string $revision = null): array {
    $revision = $revision ?? $this->githubController->getLatestCommitRevisionOnBranch($branch);
    if ($revision === null) {
      throw new \Exception("Couldn't find a Github revision for $branch Does the branch exist?");
    }
    // Latest Pipeline for release branch
    $params = [
      'query' => ['branch' => urlencode($branch)],
    ];
    $response = $this->httpClient->get('pipeline', $params);
    $pipelines = json_decode($response->getBody()->getContents(), true);
    $pipelineId = null;
    foreach ($pipelines['items'] as $pipeline) {
      // This is build for the revision we want
      if ($pipeline['vcs']['revision'] === $revision) {
        $pipelineId = $pipeline['id'];
      }
    }

    if ($pipelineId === null) {
      throw new \Exception("No ZIP build found for $branch ($revision).");
    }

    $responseWorkflows = $this->httpClient->get('https://circleci.com/api/v2/pipeline/' . urlencode($pipelineId) . '/workflow');
    $workflows = json_decode($responseWorkflows->getBody()->getContents(), true);
    $latestWorkFlowId = $workflows['items'][0]['id'] ?? null;
    if ($latestWorkFlowId === null) {
      throw new \Exception("No ZIP build found for $branch ($revision).");
    }

    $responseJob = $this->httpClient->get('https://circleci.com/api/v2/workflow/' . urlencode($latestWorkFlowId) . '/job');
    $jobs = json_decode($responseJob->getBody()->getContents(), true);

    foreach ($jobs['items'] as $job) {
      if ($job['name'] === self::RELEASE_ZIP_JOB_NAME) {
        return $job;
      }
    }
    throw new \Exception("No ZIP build found for $branch ($revision).");
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

  private function downloadZipFromJob(array $job, string $targetPath) {
    $releaseZipUrl = $this->getReleaseZipUrl($job['job_number']);
    $this->httpClient->get($releaseZipUrl, [
      'save_to' => $targetPath,
      'query' => [
        'circle-token' => $this->token, // artifact download requires token as query param
      ],
    ]);
  }
}
