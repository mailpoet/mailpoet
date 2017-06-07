<?php
namespace MailPoet\Services\Release;

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

    $code = wp_remote_retrieve_response_code($result);
    switch($code) {
      case 200:
        if($body = wp_remote_retrieve_body($result)) {
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
    return wp_remote_get($url, $args);
  }
}
