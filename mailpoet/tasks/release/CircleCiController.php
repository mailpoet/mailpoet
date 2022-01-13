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
      'auth' => [$token, ''],
      'headers' => [
        'Accept' => 'application/json',
      ],
      'base_uri' => 'https://circleci.com/api/v1.1/project/github/' . urlencode($username) . "/$circleCiProject/",
    ]);
    $this->githubController = $githubController;
  }

  public function downloadLatestBuild($targetPath) {
    $job = $this->getLatestZipBuildJob();
    $this->checkZipBuildJob($job);
    $releaseZipUrl = $this->getReleaseZipUrl($job['build_num']);

    $this->httpClient->get($releaseZipUrl, [
      'save_to' => $targetPath,
      'query' => [
        'circle-token' => $this->token, // artifact download requires token as query param
      ],
    ]);
    return $targetPath;
  }

  private function getLatestZipBuildJob() {
    $response = $this->httpClient->get('tree/' . urlencode(self::RELEASE_BRANCH));
    $jobs = json_decode($response->getBody()->getContents(), true);

    foreach ($jobs as $job) {
      if ($job['workflows']['job_name'] === self::RELEASE_ZIP_JOB_NAME) {
        return $job;
      }
    }
    throw new \Exception('No release ZIP build found');
  }

  private function checkZipBuildJob(array $job) {
    if ($job['status'] !== self::JOB_STATUS_SUCCESS) {
      $expectedStatus = self::JOB_STATUS_SUCCESS;
      throw new \Exception("Job has invalid status '$job[status]', '$expectedStatus' expected");
    }

    if ($job['has_artifacts'] === false) {
      throw new \Exception('Job has no artifacts');
    }

    // ensure we're downloading latest revision on given branch
    $revision = $this->githubController->getLatestCommitRevisionOnBranch(self::RELEASE_BRANCH);
    if ($revision === null || $job['vcs_revision'] !== $revision) {
      throw new \Exception(
        "Found ZIP was built from revision '$revision' but the latest one is '$job[vcs_revision]'"
      );
    }
  }

  private function getReleaseZipUrl($buildNumber) {
    $response = $this->httpClient->get("$buildNumber/artifacts");
    $artifacts = json_decode($response->getBody()->getContents(), true);

    $pattern = preg_quote($this->zipFilename, '~');
    foreach ($artifacts as $artifact) {
      if (preg_match("~/$pattern$~", $artifact['path'])) {
        return $artifact['url'];
      }
    }
    throw new \Exception('No ZIP file found in build artifacts');
  }
}
