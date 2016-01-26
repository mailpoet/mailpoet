<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class ElasticEmail {
  public $url = 'https://api.elasticemail.com/mailer/send';
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
      $this->request($newsletter, $subscriber));
    return (
      !is_wp_error($result) === true &&
      !preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']) === false
    );
  }

  function getBody($newsletter, $subscriber) {
    $body = array(
      'api_key' => $this->api_key,
      'to' => $subscriber,
      'from' => $this->sender['from_email'],
      'from_name' => $this->sender['from_name'],
      'reply_to' => $this->reply_to['reply_to_email'],
      'reply_to_name' => $this->reply_to['reply_to_name'],
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