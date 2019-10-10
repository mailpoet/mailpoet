<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GitHubController {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  const FREE_ZIP_FILENAME = 'mailpoet.zip';
  const PREMIUM_ZIP_FILENAME = 'mailpoet-premium.zip';

  const RELEASE_SOURCE_BRANCH = 'release';

  const QA_GITHUB_LOGIN = 'codemonkey-jack';

  /** @var string */
  private $zip_filename;

  /** @var HttpClient */
  private $http_client;

  public function __construct($username, $token, $project) {
    $this->zip_filename = $project === self::PROJECT_MAILPOET ? self::FREE_ZIP_FILENAME : self::PREMIUM_ZIP_FILENAME;
    $github_path = $project === self::PROJECT_MAILPOET ? 'mailpoet' : 'mailpoet-premium';
    $this->http_client = new Client([
      'auth' => [$username, $token],
      'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
      ],
      'base_uri' => "https://api.github.com/repos/mailpoet/$github_path/",
    ]);
  }

  public function createReleasePullRequest($version) {
    $response = $this->http_client->post('pulls', [
      'json' => [
        'title' => 'Release ' . $version,
        'head' => self::RELEASE_SOURCE_BRANCH,
        'base' => 'master',
      ],
    ]);
    $response = json_decode($response->getBody()->getContents(), true);
    $pull_request_number = $response['number'];
    if (!$pull_request_number) {
      throw new \Exception('Failed to create a new release pull request');
    }
    $this->assignPullRequest($pull_request_number);
  }

  private function assignPullRequest($pull_request_number) {
    $this->http_client->post("pulls/$pull_request_number/requested_reviewers", [
      'json' => ['reviewers' => [self::QA_GITHUB_LOGIN]],
    ]);
    $this->http_client->post("issues/$pull_request_number/assignees", [
      'json' => ['assignees' => [self::QA_GITHUB_LOGIN]],
    ]);
  }

  public function checkReleasePullRequestPassed($version) {
    $response = $this->http_client->get('pulls', [
      'query' => [
        'state' => 'all',
        'head' => self::RELEASE_SOURCE_BRANCH,
        'base' => 'master',
        'direction' => 'desc',
      ],
    ]);
    $response = json_decode($response->getBody()->getContents(), true);
    if (sizeof($response) === 0) {
      throw new \Exception('Failed to load release pull requests');
    }
    $response = array_filter($response, function ($pull_request) use ($version) {
      return strpos($pull_request['title'], 'Release ' . $version) !== false;
    });
    if (sizeof($response) === 0) {
      throw new \Exception('Release pull request not found');
    }
    $release_pull_request = reset($response);
    $this->checkPullRequestChecks($release_pull_request['statuses_url']);
    $pull_request_number = $release_pull_request['number'];
    $this->checkPullRequestReviews($pull_request_number);
  }

  private function checkPullRequestChecks($statuses_url) {
    $response = $this->http_client->get($statuses_url);
    $response = json_decode($response->getBody()->getContents(), true);

    // Find checks. Statuses are returned in reverse chronological order. We need to get the first of each type
    $latest_statuses = [];
    foreach ($response as $status) {
      if (!isset($latest_statuses[$status['context']])) {
        $latest_statuses[$status['context']] = $status;
      }
    }

    $failed = [];
    foreach ($latest_statuses as $status) {
      if ($status['state'] !== 'success') {
        $failed[] = $status['context'];
      }
    }
    if (!empty($failed)) {
      throw new \Exception('Release pull request build failed. Failed jobs: ' . join(', ', $failed));
    }
  }

  private function checkPullRequestReviews($pull_request_number) {
    $response = $this->http_client->get("pulls/$pull_request_number/reviews");
    $response = json_decode($response->getBody()->getContents(), true);
    $approved = 0;
    foreach ($response as $review) {
      if (strtolower($review['state']) === 'approved') {
        $approved++;
      }
    }
    if ($approved === 0) {
      throw new \Exception('Pull Request has not been approved');
    }
  }

  public function publishRelease($version, $changelog, $release_zip_path) {
    $this->ensureNoDraftReleaseExists();
    $this->ensureReleaseDoesNotExistYet($version);
    $release_info = $this->createReleaseDraft($version, $changelog);

    // remove {?name,label} from the end of 'upload_url'
    $upload_url = preg_replace('/\{[^{}]+\}$/ui', '', $release_info['upload_url']);
    $this->uploadReleaseZip($upload_url, $release_zip_path);
    $this->publishDraftAsRelease($release_info['id']);
  }

  public function getLatestCommitRevisionOnBranch($branch) {
    try {
      $response = $this->http_client->get('commits/' . urlencode($branch));
      $data = json_decode($response->getBody()->getContents(), true);
    } catch (ClientException $e) {
      if ($e->getCode() === 404) {
        return null;
      }
      throw $e;
    }
    return $data['sha'];
  }

  private function ensureNoDraftReleaseExists() {
    $response = $this->http_client->get('releases');
    $data = json_decode($response->getBody()->getContents(), true);
    if (is_array($data) && count($data) > 0 && $data[0]['draft']) {
      throw new \Exception('There are unpublished draft releases');
    }
  }

  private function ensureReleaseDoesNotExistYet($version) {
    try {
      $this->http_client->get('releases/tags/' . urlencode($version));
      $existing = true;
    } catch (ClientException $e) {
      if ($e->getCode() !== 404) {
        throw $e;
      }
      $existing = false;
    }

    if ($existing) {
      throw new \Exception("Release with version '$version' already exists");
    }
  }

  private function createReleaseDraft($version, $changelog) {
    $response = $this->http_client->post('releases', [
      'json' => [
        'draft' => true,
        'name' => $version,
        'tag_name' => $version,
        'target_commitish' => self::RELEASE_SOURCE_BRANCH,
        'body' => $changelog,
      ],
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  private function uploadReleaseZip($upload_url, $release_zip_path) {
    $this->http_client->post($upload_url, [
      'headers' => [
        'Content-Type' => 'application/zip',
      ],
      'query' => [
        'name' => $this->zip_filename,
      ],
      'body' => fopen($release_zip_path, 'rb'),
    ]);
  }

  private function publishDraftAsRelease($release_id) {
    $this->http_client->patch('releases/' . urlencode($release_id), [
      'json' => [
        'draft' => false,
      ],
    ]);
  }
}
