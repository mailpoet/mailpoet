<?php
namespace MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class API {
  public $url = 'https://bridge.mailpoet.com/api/v0/me';
  public $api_key;

  function __construct($api_key) {
    $this->setKey($api_key);
  }

  function checkKey() {
    $result = wp_remote_post(
      $this->url,
      $this->request(array('site' => home_url()))
    );
    return $this->processResponse($result);
  }

  function setKey($api_key) {
    $this->api_key = $api_key;
  }

  private function processResponse($result) {
    $code = wp_remote_retrieve_response_code($result);
    switch($code) {
      case 200:
      case 402:
        $body = json_decode(wp_remote_retrieve_body($result), true);
        break;
      case 401:
        $body = wp_remote_retrieve_body($result);
        break;
      default:
        $body = null;
        break;
    }

    return array('code' => $code, 'data' => $body);
  }

  private function auth() {
    return 'Basic ' . base64_encode('api:' . $this->api_key);
  }

  private function request($body) {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => $this->auth()
      ),
      'body' => json_encode($body)
    );
  }
}
