<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GitHubController {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  const FREE_ZIP_FILENAME = 'mailpoet.zip';
  const PREMIUM_ZIP_FILENAME = 'mailpoet-premium.zip';

  const RELEASE_SOURCE_BRANCH = 'master';

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

  public function publishRelease($version, $changelog, $release_zip_path) {
    $this->ensureNoDraftReleaseExists();
    $this->ensureReleaseDoesNotExistYet($version);
    $release_info = $this->createReleaseDraft($version, $changelog);

    // remove {?name,label} from the end of 'upload_url'
    $upload_url = preg_replace('/\{[^{}]+\}$/ui', '', $release_info['upload_url']);
    $this->uploadReleaseZip($upload_url, $release_zip_path);
    $this->publishDraftAsRelease($release_info['id']);
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

  private function uploadReleaseZip($upload_url, $release_zip_path)
  {
    $this->http_client->post($upload_url, [
      'headers' => [
        'Content-Type' => 'application/zip'
      ],
      'query' => [
        'name' => $this->zip_filename,
      ],
      'body' => fopen($release_zip_path, 'rb'),
    ]);
  }

  private function publishDraftAsRelease($release_id)
  {
    $this->http_client->patch('releases/' . urlencode($release_id), [
      'json' => [
        'draft' => false,
      ],
    ]);
  }
}
