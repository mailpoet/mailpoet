<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class SendGrid {
  function __construct($api_key, $from) {
    $this->url = 'https://api.sendgrid.com/api/mail.send.json';
    $this->api_key = $api_key;
    $this->from = $from;
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    $result = json_decode($result['body'], true);
    return (!isset($result['errors']) === true);
  }

  function getBody() {
    $parameters = array(
      'to' => $this->subscriber,
      'from' => $this->from,
      'subject' => $this->newsletter['subject'],
      'html' => $this->newsletter['body']['html'],
      'text' => $this->newsletter['body']['text']
    );
    return urldecode(http_build_query($parameters));
  }

  function auth() {
    return 'Bearer ' . $this->api_key;
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Authorization' => $this->auth()
      ),
      'body' => $this->getBody()
    );
  }
}