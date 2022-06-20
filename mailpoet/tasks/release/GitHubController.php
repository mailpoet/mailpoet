<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GitHubController {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';
  const PROJECT_SHOP = 'SHOP';

  const FREE_ZIP_FILENAME = 'mailpoet.zip';
  const PREMIUM_ZIP_FILENAME = 'mailpoet-premium.zip';

  const RELEASE_SOURCE_BRANCH = 'release';

  const QA_GITHUB_LOGIN = 'veljkho';

  private const API_BASE_URI = 'https://api.github.com/repos/mailpoet';

  /** @var string */
  private $zipFilename;

  /** @var Client */
  private $httpClient;

  public function __construct(
    $username,
    $token,
    $project
  ) {
    $this->zipFilename = $project === self::PROJECT_MAILPOET ? self::FREE_ZIP_FILENAME : self::PREMIUM_ZIP_FILENAME;
    $this->httpClient = new Client([
      'auth' => [$username, $token],
      'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
      ],
      'base_uri' => self::API_BASE_URI . "/{$this->getGithubPathByProject($project)}/",
    ]);
  }

  /**
   * @return array{login: string, count: int}
   */
  public function calculateReviewers(): array {
    // load pull requests
    $response = $this->httpClient->get('pulls', [
      'query' => [
        'per_page' => '100', // 100 is maximum, if we want more we need to implement paging
        'state' => 'all',
      ],
    ]);
    $response = json_decode($response->getBody()->getContents(), true);
    $logins = [];
    foreach ($response as $item) {
      $author = $item['user']['login'];
      // calculate reviewers - here we only get reviewers if the review has not yet started
      $reviewers = $item['requested_reviewers'];
      foreach ($reviewers as $reviewer) {
        if ($reviewer['login'] === self::QA_GITHUB_LOGIN) continue;
        if ($reviewer['login'] === $author) continue;
        $logins[$reviewer['login']] = ($logins[$reviewer['login']] ?? 0) + 1;
      }

      $single = [];
      $pullRequestNumber = $item['number'];
      // load all performed reviews (approved, request changes, add a comment)
      $reviews = $this->httpClient->get("pulls/$pullRequestNumber/reviews");
      $reviews = json_decode($reviews->getBody()->getContents(), true);
      foreach ($reviews as $review) {
        $log = $review['user']['login'];
        if ($log === self::QA_GITHUB_LOGIN) continue;
        if ($log === $author) continue;
        // each person only once for each pull request. We don't want to count them more times if they added more comments
        $single[$log] = $log;
      }
      foreach ($single as $log) {
        $logins[$log] = ($logins[$log] ?? 0) + 1;
      }
    }

    // merge Alex
    if (!isset($logins['alex-mailpoet'])) {
      $logins['alex-mailpoet'] = 0;
    }
    if (isset($logins['wxa'])) {
      $logins['alex-mailpoet'] += $logins['wxa'];
      unset($logins['wxa']);
    }
    return $logins;
  }

  public function createReleasePullRequest($version) {
    $response = $this->httpClient->post('pulls', [
      'json' => [
        'title' => 'Release ' . $version,
        'head' => self::RELEASE_SOURCE_BRANCH,
        'base' => 'trunk',
      ],
    ]);
    $response = json_decode($response->getBody()->getContents(), true);
    $pullRequestNumber = $response['number'];
    if (!$pullRequestNumber) {
      throw new \Exception('Failed to create a new release pull request');
    }
    $this->assignPullRequest($pullRequestNumber);
  }

  private function assignPullRequest($pullRequestNumber) {
    $this->httpClient->post("pulls/$pullRequestNumber/requested_reviewers", [
      'json' => ['reviewers' => [self::QA_GITHUB_LOGIN]],
    ]);
    $this->httpClient->post("issues/$pullRequestNumber/assignees", [
      'json' => ['assignees' => [self::QA_GITHUB_LOGIN]],
    ]);
  }

  public function checkReleasePullRequestPassed($version) {
    $response = $this->httpClient->get('pulls', [
      'query' => [
        'state' => 'all',
        'head' => self::RELEASE_SOURCE_BRANCH,
        'base' => 'trunk',
        'direction' => 'desc',
      ],
    ]);
    $response = json_decode($response->getBody()->getContents(), true);
    if (sizeof($response) === 0) {
      throw new \Exception('Failed to load release pull requests');
    }
    $response = array_filter($response, function ($pullRequest) use ($version) {
      return strpos($pullRequest['title'], 'Release ' . $version) !== false;
    });
    if (sizeof($response) === 0) {
      throw new \Exception('Release pull request not found');
    }
    $releasePullRequest = reset($response);
    $this->checkPullRequestChecks($releasePullRequest['statuses_url']);
    $pullRequestNumber = $releasePullRequest['number'];
    $this->checkPullRequestReviews($pullRequestNumber);
  }

  private function checkPullRequestChecks($statusesUrl) {
    $response = $this->httpClient->get($statusesUrl);
    $response = json_decode($response->getBody()->getContents(), true);

    // Find checks. Statuses are returned in reverse chronological order. We need to get the first of each type
    $latestStatuses = [];
    foreach ($response as $status) {
      if (!isset($latestStatuses[$status['context']])) {
        $latestStatuses[$status['context']] = $status;
      }
    }

    $failed = [];
    foreach ($latestStatuses as $status) {
      if ($status['state'] !== 'success') {
        $failed[] = $status['context'];
      }
    }
    if (!empty($failed)) {
      throw new \Exception('Release pull request build failed. Failed jobs: ' . join(', ', $failed));
    }
  }

  private function checkPullRequestReviews($pullRequestNumber) {
    $response = $this->httpClient->get("pulls/$pullRequestNumber/reviews");
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

  public function publishRelease($version, $changelog, $releaseZipPath) {
    $this->ensureNoDraftReleaseExists();
    $this->ensureReleaseDoesNotExistYet($version);
    $releaseInfo = $this->createReleaseDraft($version, $changelog);

    // remove {?name,label} from the end of 'upload_url'
    $uploadUrl = preg_replace('/\{[^{}]+\}$/ui', '', $releaseInfo['upload_url']);
    $this->uploadReleaseZip($uploadUrl, $releaseZipPath);
    $this->publishDraftAsRelease($releaseInfo['id']);
  }

  public function getLatestCommitRevisionOnBranch($branch) {
    try {
      $response = $this->httpClient->get('commits/' . urlencode($branch));
      $data = json_decode($response->getBody()->getContents(), true);
    } catch (ClientException $e) {
      if ($e->getCode() === 404 || $e->getCode() === 422) {
        return null;
      }
      throw $e;
    }
    return $data['sha'];
  }

  public function projectBranchExists(string $project, string $branch): bool {
    $githubProject = $this->getGithubPathByProject($project);
    $branch = urlencode($branch);
    try {
      $this->httpClient->get(
        self::API_BASE_URI . "/{$githubProject}/git/ref/heads/{$branch}"
      );
    } catch (ClientException $e) {
      if ($e->getCode() === 404) {
        return false;
      }
      throw $e;
    }
    return true;
  }

  private function ensureNoDraftReleaseExists() {
    $response = $this->httpClient->get('releases');
    $data = json_decode($response->getBody()->getContents(), true);
    if (is_array($data) && count($data) > 0 && $data[0]['draft']) {
      throw new \Exception('There are unpublished draft releases');
    }
  }

  private function ensureReleaseDoesNotExistYet($version) {
    try {
      $this->httpClient->get('releases/tags/' . urlencode($version));
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
    $response = $this->httpClient->post('releases', [
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

  private function uploadReleaseZip($uploadUrl, $releaseZipPath) {
    $this->httpClient->post($uploadUrl, [
      'headers' => [
        'Content-Type' => 'application/zip',
      ],
      'query' => [
        'name' => $this->zipFilename,
      ],
      'body' => fopen($releaseZipPath, 'rb'),
    ]);
  }

  private function publishDraftAsRelease($releaseId) {
    $this->httpClient->patch('releases/' . urlencode($releaseId), [
      'json' => [
        'draft' => false,
      ],
    ]);
  }

  private function getGithubPathByProject(string $project): string {
    $url = 'mailpoet-premium';
    if ($project === self::PROJECT_MAILPOET) {
      $url = 'mailpoet';
    } elseif ($project === self::PROJECT_SHOP) {
      $url = 'shop';
    }
    return urlencode($url);
  }
}
