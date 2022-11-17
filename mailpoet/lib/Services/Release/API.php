<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Services\Release;

use MailPoet\WP\Functions as WPFunctions;

class API {
  private $apiKey;
  private $wp;
  public $urlProducts = 'https://release.mailpoet.com/products/';

  public function __construct(
    $apiKey
  ) {
    $this->setKey($apiKey);
    $this->wp = new WPFunctions();
  }

  public function getPluginInformation($pluginName) {
    $result = $this->request(
      $this->urlProducts . $pluginName
    );

    $code = $this->wp->wpRemoteRetrieveResponseCode($result);
    switch ($code) {
      case 200:
        $body = $this->wp->wpRemoteRetrieveBody($result);
        if ($body) {
          $body = json_decode($body);
        }
        break;
      default:
        $body = null;
        break;
    }

    return $body;
  }

  public function setKey($apiKey) {
    $this->apiKey = $apiKey;
  }

  public function getKey() {
    return $this->apiKey;
  }

  private function request($url, $params = []) {
    $params['license'] = $this->apiKey;
    $url = WPFunctions::get()->addQueryArg($params, $url);
    $args = [
      'timeout' => 10,
      'httpversion' => '1.0',
    ];
    return $this->wp->wpRemoteGet($url, $args);
  }
}
