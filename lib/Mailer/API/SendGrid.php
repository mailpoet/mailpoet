<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class SendGrid {
  function __construct($apiKey, $fromEmail, $fromName) {
    $this->url = 'https://api.sendgrid.com/api/mail.send.json';
    $this->apiKey = $apiKey;
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
  }

  function send($newsletter, $subscriber) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber)
    );
    return (
      !is_wp_error($result) === true &&
      !preg_match('!invalid!', $result['body']) === true &&
      !isset(json_decode($result['body'], true)['errors']) === true &&
      wp_remote_retrieve_response_code($result) === 200
    );
  }

  function getBody($newsletter, $subscriber) {
    return array(
      'to' => $subscriber,
      'from' => $this->fromEmail,
      'fromname' => $this->fromName,
      'subject' => $newsletter['subject'],
      'html' => $newsletter['body']['html'],
      'text' => $newsletter['body']['text']
    );
  }

  function auth() {
    return 'Bearer ' . $this->apiKey;
  }

  function request($newsletter, $subscriber) {
    $body = $this->getBody($newsletter, $subscriber);
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Authorization' => $this->auth()
      ),
      'body' => urldecode(http_build_query($body))
    );
  }
}