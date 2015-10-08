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
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    return (
      !is_wp_error($result) === true &&
      !preg_match('!invalid!', $result['body']) === true &&
      !isset(json_decode($result['body'], true)['errors']) === true &&
      wp_remote_retrieve_response_code($result) === 200
    );
  }

  function getBody() {
    return array(
      'to' => $this->subscriber,
      'from' => $this->fromEmail,
      'fromname' => $this->fromName,
      'subject' => $this->newsletter['subject'],
      'html' => $this->newsletter['body']['html'],
      'text' => $this->newsletter['body']['text']
    );
  }

  function auth() {
    return 'Bearer ' . $this->apiKey;
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Authorization' => $this->auth()
      ),
      'body' => urldecode(http_build_query($this->getBody()))
    );
  }
}