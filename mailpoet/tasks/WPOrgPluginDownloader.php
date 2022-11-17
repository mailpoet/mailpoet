<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks;

use GuzzleHttp\Client;

class WPOrgPluginDownloader {
  /** @var Client */
  private $httpClient;

  private const API_BASE_URI = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information';

  private $pluginSlug;

  private $apiUrl;

  public function __construct(
    $pluginSlug
  ) {
    $this->pluginSlug = $pluginSlug;
    $this->apiUrl = self::API_BASE_URI . "&request[slug]=$pluginSlug";
    $this->httpClient = new Client();
  }

  public function downloadPluginZip($zip, $downloadDir, $tag = null) {
    $downloadLink = $this->getDownloadLink($tag);

    if (!is_dir($downloadDir)) {
      mkdir($downloadDir, 0777, true);
    }

    $this->httpClient->get($downloadLink, ['sink' => $downloadDir . $zip, 'headers' => ['Accept' => 'application/octet-stream']]);
    file_put_contents($downloadDir . '/' . $zip . '-info', $downloadLink);
  }

  private function getDownloadLink($tag = null) {
    $pluginInfo = $this->getPluginInformation();

    if (!$pluginInfo) {
      throw new \Exception("Plugin {$this->pluginSlug} not found");
    }

    if ($pluginInfo['slug'] !== $this->pluginSlug) {
      throw new \Exception("Error with data gotten from WordPress.org");
    }

    // use the latest version if tag is not specified
    if (empty($tag) || $tag === 'latest') {
      return $pluginInfo['download_link'];
    }

    $recentVersion = $pluginInfo['version'];
    if (!array_key_exists($tag, $pluginInfo['versions'])) {
      throw new \Exception("Plugin zip for version $tag not found. Most recent version is $recentVersion");
    }

    return $pluginInfo['versions'][$tag];
  }

  private function getPluginInformation() {
    $response = $this->httpClient->get($this->apiUrl);
    return json_decode($response->getBody()->getContents(), true);
  }
}
