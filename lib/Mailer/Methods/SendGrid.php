<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class SendGrid {
  public $url = 'https://api.sendgrid.com/api/mail.send.json';
  public $api_key;
  public $from_email;
  public $from_name;

  function __construct($api_key, $from_email, $from_name) {
    $this->api_key = $api_key;
    $this->from_email = $from_email;
    $this->from_name = $from_name;
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
    $body = array(
      'to' => $subscriber,
      'from' => $this->from_email,
      'from_name' => $this->from_name,
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
    return 'Bearer ' . $this->api_key;
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