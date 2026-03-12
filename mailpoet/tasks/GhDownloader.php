<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks;

// phpcs:disable WordPress.WP.AlternativeFunctions -- build task running outside WordPress
class GhDownloader {
  const RETRIES_COUNT = 3;
  const SECONDS_SLEEP_BEFORE_RETRY = 10;
  const API_BASE_URI = 'https://api.github.com/repos';

  /** @var bool */
  private $hasGh;

  /** @var string|null */
  private $ghToken;

  public function __construct() {
    $this->hasGh = $this->detectGhCli();
    $this->ghToken = getenv('GH_TOKEN') ?: null;
  }

  public function isAuthenticated(): bool {
    if ($this->hasGh) {
      $output = [];
      $exitCode = 0;
      exec('gh auth status --hostname github.com 2>&1', $output, $exitCode);
      if ($exitCode === 0) {
        return true;
      }
    }
    return $this->ghToken !== null;
  }

  public function downloadReleaseAsset(string $repo, string $assetPattern, string $destPath, ?string $tag = null): void {
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    if ($this->hasGh) {
      $this->downloadReleaseAssetWithGh($repo, $assetPattern, $destPath, $tag);
    } else {
      $this->downloadReleaseAssetWithCurl($repo, $assetPattern, $destPath, $tag);
    }
  }

  public function downloadRawFile(string $repo, string $filePath, string $ref, string $destPath): void {
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    $url = self::API_BASE_URI . "/$repo/contents/$filePath?ref=$ref";

    if ($this->hasGh) {
      $apiPath = sprintf('repos/%s/contents/%s?ref=%s', $repo, $filePath, $ref);
      $cmd = sprintf(
        'gh api %s -H %s > %s',
        escapeshellarg($apiPath),
        escapeshellarg('Accept: application/vnd.github.v3.raw'),
        escapeshellarg($destPath)
      );
      $this->exec($cmd);
    } else {
      $this->curlDownload($url, $destPath, 'application/vnd.github.v3.raw');
    }
  }

  private function downloadReleaseAssetWithGh(string $repo, string $assetPattern, string $destPath, ?string $tag): void {
    $tmpDir = sys_get_temp_dir() . '/gh-download-' . uniqid();
    mkdir($tmpDir, 0777, true);

    $tagArg = ($tag && $tag !== 'latest') ? escapeshellarg($tag) : '';
    $cmd = sprintf(
      'gh release download %s --repo %s --pattern %s --dir %s --clobber',
      $tagArg,
      escapeshellarg($repo),
      escapeshellarg($assetPattern),
      escapeshellarg($tmpDir)
    );
    $this->exec($cmd);

    $files = glob($tmpDir . '/*.zip');
    if (empty($files)) {
      $this->cleanup($tmpDir);
      throw new \Exception("No matching asset found for pattern '$assetPattern' in $repo");
    }

    rename($files[0], $destPath);
    $this->cleanup($tmpDir);
  }

  private function downloadReleaseAssetWithCurl(string $repo, string $assetPattern, string $destPath, ?string $tag): void {
    $release = $this->curlGetJson(
      self::API_BASE_URI . "/$repo/releases/" . ($tag && $tag !== 'latest' ? "tags/$tag" : 'latest')
    );

    if (!$release || !isset($release['assets'])) {
      throw new \Exception("Release $tag not found for $repo");
    }

    $zip = basename($destPath);
    $namesToCheck = [$zip];
    if ($tag) {
      $namesToCheck[] = str_replace('.zip', ".$tag.zip", $zip);
      $namesToCheck[] = str_replace('.zip', "-$tag.zip", $zip);
    }
    $lastVersion = $release['tag_name'] ?? null;
    if ($lastVersion && $lastVersion !== $tag) {
      $namesToCheck[] = str_replace('.zip', ".$lastVersion.zip", $zip);
      $namesToCheck[] = str_replace('.zip', "-$lastVersion.zip", $zip);
    }

    $assetUrl = null;
    $assetInfo = null;
    foreach ($release['assets'] as $asset) {
      if (in_array($asset['name'], $namesToCheck, true)) {
        $assetUrl = $asset['url'];
        $assetInfo = $asset['browser_download_url'];
      }
    }

    if (!$assetUrl) {
      throw new \Exception("Release zip for $tag not found in $repo");
    }

    $this->curlDownload($assetUrl, $destPath, 'application/octet-stream');
  }

  private function curlGetJson(string $url): ?array {
    $retries = 0;
    while (true) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'MailPoet-CI');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        "Authorization: Bearer {$this->ghToken}",
      ]);
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($httpCode >= 200 && $httpCode < 300 && $response !== false) {
        return json_decode($response, true);
      }

      if ($httpCode === 401 || $httpCode === 403) {
        throw new \Exception("Authentication failed for $url (HTTP $httpCode). Check your GH_TOKEN.");
      }

      if ($httpCode >= 500 && $retries++ < self::RETRIES_COUNT) {
        sleep(self::SECONDS_SLEEP_BEFORE_RETRY);
        continue;
      }

      return null;
    }
  }

  private function curlDownload(string $url, string $destPath, string $accept): void {
    $retries = 0;
    while (true) {
      $fp = fopen($destPath, 'w');
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'MailPoet-CI');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: $accept",
        "Authorization: Bearer {$this->ghToken}",
      ]);
      curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      fclose($fp);

      if ($httpCode >= 200 && $httpCode < 300) {
        return;
      }

      if ($httpCode >= 500 && $retries++ < self::RETRIES_COUNT) {
        sleep(self::SECONDS_SLEEP_BEFORE_RETRY);
        continue;
      }

      @unlink($destPath);
      throw new \Exception("Failed to download $url (HTTP $httpCode)");
    }
  }

  private function detectGhCli(): bool {
    $output = [];
    $exitCode = 0;
    exec('which gh 2>/dev/null', $output, $exitCode);
    return $exitCode === 0;
  }

  private function exec(string $command): string {
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
      throw new \Exception("Command failed (exit $exitCode): $command\n" . implode("\n", $output));
    }
    return implode("\n", $output);
  }

  private function cleanup(string $dir): void {
    $files = glob($dir . '/*');
    if ($files) {
      foreach ($files as $file) {
        unlink($file);
      }
    }
    rmdir($dir);
  }
}
