<?php

namespace MailPoetTasks;

use GuzzleHttp\Client;

class GithubClient {
  /** @var Client */
  private $httpClient;

  private const API_BASE_URI = 'https://api.github.com/repos';

  public function __construct($username, $token, $repo) {
    $this->httpClient = new Client([
      'auth' => [$username, $token],
      'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
      ],
      'base_uri' => self::API_BASE_URI . "/$repo/",
    ]);
  }

  public function downloadReleaseZip($zip, $downloadDir, $tag = null) {
    $release = $this->getRelease($tag);
    if (!$release) {
      throw new \Exception("Release $tag not found");
    }
    $assetDownloadUrl = null;
    foreach ($release['assets'] as $asset) {
      if ($asset['name'] === $zip) {
        $assetDownloadUrl = $asset['url'];
      }
    }
    if (!$assetDownloadUrl) {
      throw new \Exception("Release zip for $tag not found");
    }
    $this->httpClient->get($assetDownloadUrl, ['sink' => $downloadDir . $zip, 'headers' => ['Accept' => 'application/octet-stream']]);
  }

  private function getRelease($tag = null) {
    $path = 'releases/' . ($tag ? "tags/$tag" : 'latest');
    $response = $this->httpClient->get($path);
    return json_decode($response->getBody()->getContents(), true);
  }
}
