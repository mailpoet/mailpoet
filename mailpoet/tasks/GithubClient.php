<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

class GithubClient {
  const RETRIES_COUNT = 3;
  const SECONDS_SLEEP_BEFORE_RETRY = 10;

  /** @var Client */
  private $httpClient;

  private const API_BASE_URI = 'https://api.github.com/repos';

  public function __construct(
    $repo,
    $username = null,
    $token = null
  ) {
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
    $namesToCheck = [$zip];
    if ($tag) {
      $namesToCheck[] = str_replace('.zip', ".$tag.zip", $zip);
      $namesToCheck[] = str_replace('.zip', "-$tag.zip", $zip);
    }
    // We use the last version name to find a zip file
    $lastVersion = $release['tag_name'];
    if ($lastVersion !== $tag) {
      $namesToCheck[] = str_replace('.zip', ".$lastVersion.zip", $zip);
      $namesToCheck[] = str_replace('.zip', "-$lastVersion.zip", $zip);
    }

    $assetDownloadUrl = null;
    $assetDownloadInfo = null;
    foreach ($release['assets'] as $asset) {
      if (in_array($asset['name'], $namesToCheck, true)) {
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
    $this->get($assetDownloadUrl, ['sink' => $downloadDir . $zip, 'headers' => ['Accept' => 'application/octet-stream']]);
    file_put_contents($downloadDir . '/' . $zip . '-info', $assetDownloadInfo);
  }

  private function getRelease($tag = null) {
    $path = 'releases/' . ($tag && $tag !== 'latest' ? "tags/$tag" : 'latest');
    $response = $this->get($path);
    return json_decode($response->getBody()->getContents(), true);
  }

  private function get($uri, array $options = []): ResponseInterface {
    $retries = 0;
    while (true) {
      try {
        return $this->httpClient->get($uri, $options);
      } catch (ServerException $e) {
        if ($retries++ >= self::RETRIES_COUNT) {
          throw $e;
        }
        sleep(self::SECONDS_SLEEP_BEFORE_RETRY);
      }
    }
  }
}
