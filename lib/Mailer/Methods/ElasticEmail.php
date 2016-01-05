<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class ElasticEmail {
  function __construct($apiKey, $fromEmail, $fromName) {
    $this->url = 'https://api.elasticemail.com/mailer/send';
    $this->apiKey = $apiKey;
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
  }

  function send($newsletter, $subscriber) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber));
    return (
      !is_wp_error($result) === true &&
      !preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']) === false
    );
  }

  function getBody($newsletter, $subscriber) {
    $body = array(
      'api_key' => $this->apiKey,
      'from' => $this->fromEmail,
      'from_name' => $this->fromName,
      'to' => $subscriber,
      'subject' => $newsletter['subject']
    );
    if(!empty($newsletter['body']['html'])) {
      $body['body_html'] = $newsletter['body']['html'];
    }
    if(!empty($newsletter['body']['text'])) {
      $body['body_text'] = $newsletter['body']['text'];
    }
    return $body;
  }

  function request($newsletter, $subscriber) {
    $body = $this->getBody($newsletter, $subscriber);
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'body' => urldecode(http_build_query($body))
    );
  }
}