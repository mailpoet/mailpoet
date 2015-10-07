<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class MailGun {
  function __construct($domain, $api_key, $from) {
    $this->url = 'https://api.mailgun.net/v3';
    $this->domain = $domain;
    $this->api_key = $api_key;
    $this->from = $from;
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $result = wp_remote_post(
      $this->url . '/' . $this->domain . '/messages',
      $this->request()
    );
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    return ($result['response']['code'] === 200);
  }

  function getBody() {
    $parameters = array(
      'from' => $this->from,
      'to' => $this->subscriber,
      'subject' => $this->newsletter['subject'],
      'html' => $this->newsletter['body']['html'],
      'text' => $this->newsletter['body']['text']
    );
    return urldecode(http_build_query($parameters));
  }

  function auth() {
    return 'Basic ' . base64_encode('api:' . $this->api_key);
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Authorization' => $this->auth()
      ),
      'body' => $this->getBody()
    );
  }
}