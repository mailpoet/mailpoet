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
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber));
    return (
      !is_wp_error($result) === true &&
      !preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']) === false
    );
  }

  function getBody($newsletter, $subscriber) {
    return array(
      'api_key' => $this->apiKey,
      'from' => $this->fromEmail,
      'from_name' => $this->fromName,
      'to' => $subscriber,
      'subject' => $newsletter['subject'],
      'body_html' => $newsletter['body']['html'],
      'body_text' => $newsletter['body']['text']
    );
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