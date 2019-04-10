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
  private $zip_filename;

  /** @var Client */
  private $http_client;

  /** @var GitHubController */
  private $github_controller;

  public function __construct($username, $token, $project, GitHubController $github_controller) {
    $this->token = $token;
    $circle_ci_project = $project === self::PROJECT_MAILPOET ? 'mailpoet' : 'mailpoet-premium';
    $this->zip_filename = $project === self::PROJECT_MAILPOET ? self::FREE_ZIP_FILENAME : self::PREMIUM_ZIP_FILENAME;
    $this->http_client = new Client([
      'auth' => [$token, ''],
      'headers' => [
        'Accept' => 'application/json',
      ],
      'base_uri' => 'https://circleci.com/api/v1.1/project/github/' . urlencode($username) . "/$circle_ci_project/",
    ]);
  }

  public function downloadLatestBuild($target_path) {
    $job = $this->getLatestZipBuildJob();
    $this->checkZipBuildJob($job);
    $release_zip_url = $this->getReleaseZipUrl($job['build_num']);

    $this->http_client->get($release_zip_url, [
      'save_to' => $target_path,
      'query' => [
        'circle-token' => $this->token, // artifact download requires token as query param
      ],
    ]);
    return $target_path;
  }

  private function getLatestZipBuildJob() {
    $response = $this->http_client->get('tree/' . urlencode(self::RELEASE_BRANCH));
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
      $expected_status = self::JOB_STATUS_SUCCESS;
      throw new \Exception("Job has invalid status '$job[status]', '$expected_status' expected");
    }

    if ($job['has_artifacts'] === false) {
      throw new \Exception('Job has no artifacts');
    }
  }

  private function getReleaseZipUrl($build_number) {
    $response = $this->http_client->get("$build_number/artifacts");
    $artifacts = json_decode($response->getBody()->getContents(), true);

    $pattern = preg_quote($this->zip_filename, '~');
    foreach ($artifacts as $artifact) {
      if (preg_match("~/$pattern$~", $artifact['path'])) {
        return $artifact['url'];
      }
    }
    throw new \Exception('No ZIP file found in build artifacts');
  }
}
