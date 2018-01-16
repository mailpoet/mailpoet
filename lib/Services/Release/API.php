<?php

namespace MailPoet\Services\Release;

use MailPoet\WP\Functions as WPFunctions;

if(!defined('ABSPATH')) exit;

class API {
  private $api_key;

  public $url_products = 'https://release.mailpoet.com/products/';

  function __construct($api_key) {
    $this->setKey($api_key);
  }

  function getPluginInformation($plugin_name) {
    $result = $this->request(
      $this->url_products . $plugin_name
    );

    $code = WPFunctions::wpRemoteRetrieveResponseCode($result);
    switch($code) {
      case 200:
        if($body = WPFunctions::wpRemoteRetrieveBody($result)) {
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

  private function request($url, $params = array()) {
    $params['license'] = $this->api_key;
    $url = add_query_arg($params, $url);
    $args = array(
      'timeout' => 10,
      'httpversion' => '1.0'
    );
    return WPFunctions::wpRemoteGet($url, $args);
  }
}
