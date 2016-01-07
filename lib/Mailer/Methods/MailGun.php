<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class MailGun {
  public $url;
  public $api_key;
  public $from;
  
  function __construct($domain, $api_key, $from) {
    $this->url = sprintf('https://api.mailgun.net/v3/%s/messages', $domain);
    $this->api_key = $api_key;
    $this->from = $from;
  }

  function send($newsletter, $subscriber) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber)
    );
    return (
      !is_wp_error($result) === true &&
      wp_remote_retrieve_response_code($result) === 200
    );
  }

  function getBody($newsletter, $subscriber) {
    $body = array(
      'from' => $this->from,
      'to' => $subscriber,
      'subject' => $newsletter['subject']
    );
    if(!empty($newsletter['body']['html'])) {
      $body['html'] = $newsletter['body']['html'];
    }
    if(!empty($newsletter['body']['text'])) {
      $body['text'] = $newsletter['body']['text'];
    }
    return $body;
  }

  function auth() {
    return 'Basic ' . base64_encode('api:' . $this->api_key);
  }

  function request($newsletter, $subscriber) {
    $body = $this->getBody($newsletter, $subscriber);
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Authorization' => $this->auth()
      ),
      'body' => urldecode(http_build_query($body))
    );
  }
}