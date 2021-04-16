<?php

namespace MailPoetTasks;

use GuzzleHttp\Client;

class GithubClient {
  /** @var Client */
  private $httpClient;

  private const API_BASE_URI = 'https://api.github.com/repos';

  public function __construct($repo, $username = null, $token = null) {
    $config = [
      'headers' => [
        'Accept' => 'application/vnd.github.v3+json',
      ],
      'base_uri' => self::API_BASE_URI . "/$repo/",
    ];
    if ($username && $token) {
      $config['auth'] = [$username, $token];
    }
    $this->httpClient = new Client($config);
  }

  public function downloadReleaseZip($zip, $downloadDir, $tag = null) {
    $release = $this->getRelease($tag);
    if (!$release) {
      throw new \Exception("Release $tag not found");
    }
    $assetDownloadUrl = null;
    $assetDownloadInfo = null;
    foreach ($release['assets'] as $asset) {
      if ($asset['name'] === $zip) {
        $assetDownloadUrl = $asset['url'];
        $assetDownloadInfo = $asset['browser_download_url'];
      }
    }
    if (!$assetDownloadUrl) {
      throw new \Exception("Release zip for $tag not found");
    }
    if (!is_dir($downloadDir)) {
      mkdir($downloadDir, 0777, true);
    }
    $this->httpClient->get($assetDownloadUrl, ['sink' => $downloadDir . $zip, 'headers' => ['Accept' => 'application/octet-stream']]);
    file_put_contents($downloadDir . '/' . $zip . '-info', $assetDownloadInfo);
  }

  private function getRelease($tag = null) {
    $path = 'releases/' . ($tag && $tag !== 'latest' ? "tags/$tag" : 'latest');
    $response = $this->httpClient->get($path);
    return json_decode($response->getBody()->getContents(), true);
  }
}
