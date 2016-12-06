<?php
namespace MailPoet\Cron\Workers\Bounce;

if(!defined('ABSPATH')) exit;

class API {
  public $url = 'https://bridge.mailpoet.com/api/v0/bounces/search';
  public $api_key;

  function __construct($api_key) {
    $this->api_key = $api_key;
  }

  function check(array $emails) {
    $result = wp_remote_post(
      $this->url,
      $this->request($emails)
    );
    if(wp_remote_retrieve_response_code($result) === 201) {
      return json_decode(wp_remote_retrieve_body($result), true);
    }
    return false;
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
