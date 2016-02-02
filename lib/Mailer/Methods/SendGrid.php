<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class SendGrid {
  public $url = 'https://api.sendgrid.com/api/mail.send.json';
  public $api_key;
  public $sender;
  public $reply_to;

  function __construct($api_key, $sender, $reply_to) {
    $this->api_key = $api_key;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
  }

  function send($newsletter, $subscriber) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $subscriber)
    );
    $result_body = json_decode($result['body'], true);
    return (
      !is_wp_error($result) === true &&
      !preg_match('!invalid!', $result['body']) === true &&
      !isset($result_body['errors']) === true &&
      wp_remote_retrieve_response_code($result) === 200
    );
  }

  function getBody($newsletter, $subscriber) {
    $body = array(
      'to' => $subscriber,
      'from' => $this->sender['from_email'],
      'fromname' => $this->sender['from_name'],
      'replyto' => $this->reply_to['reply_to_email'],
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
      'body' => http_build_query($body)
    );
  }
}