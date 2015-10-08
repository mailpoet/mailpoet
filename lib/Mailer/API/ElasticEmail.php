<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class ElasticEmail {
  function __construct($apiKey, $fromEmail, $fromName) {
    $this->url = 'https://api.elasticemail.com/mailer/send';
    $this->apiKey = $apiKey;
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $result = wp_remote_post(
      $this->url,
      $this->request());
    return (
      !is_wp_error($result) === true &&
      !preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']) === false
    );
  }

  function getBody() {
    return array(
      'api_key' => $this->apiKey,
      'from' => $this->fromEmail,
      'from_name' => $this->fromName,
      'to' => $this->subscriber,
      'subject' => $this->newsletter['subject'],
      'body_html' => $this->newsletter['body']['html'],
      'body_text' => $this->newsletter['body']['text']
    );
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'body' => urldecode(http_build_query($this->getBody()))
    );
  }
}