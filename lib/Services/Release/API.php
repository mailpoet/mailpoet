<?php

namespace MailPoet\Services\Release;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class API {
  private $api_key;
  private $wp;
  public $url_products = 'https://release.mailpoet.com/products/';

  function __construct($api_key) {
    $this->setKey($api_key);
    $this->wp = new WPFunctions();
  }

  function getPluginInformation($plugin_name) {
    $result = $this->request(
      $this->url_products . $plugin_name
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

  function setKey($api_key) {
    $this->api_key = $api_key;
  }

  function getKey() {
    return $this->api_key;
  }

  private function request($url, $params = []) {
    $params['license'] = $this->api_key;
    $url = WPFunctions::get()->addQueryArg($params, $url);
    $args = [
      'timeout' => 10,
      'httpversion' => '1.0',
    ];
    return $this->wp->wpRemoteGet($url, $args);
  }
}
