<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class MailPoet {
  public $url = 'https://bridge.mailpoet.com/api/messages';
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
      $this->request($newsletter, $this->processSubscriber($subscriber))
    );
    return (
      !is_wp_error($result) === true &&
      wp_remote_retrieve_response_code($result) === 201
    );
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if(!isset($subscriber_data['email'])) {
      $subscriber_data = array(
        'email' => $subscriber,
      );
    }
    return array(
      'email' => $subscriber_data['email'],
      'name' => (isset($subscriber_data['name'])) ? $subscriber_data['name'] : ''
    );
  }

  function getBody($newsletter, $subscriber) {
    $body = array(
      'to' => (array(
        'address' => $subscriber['email'],
        'name' => $subscriber['name']
      )),
      'from' => (array(
        'address' => $this->sender['from_email'],
        'name' => $this->sender['from_name']
      )),
      'reply_to' => (array(
        'address' => $this->reply_to['reply_to_email'],
        'name' => $this->reply_to['reply_to_name']
      )),
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
    $body = array($this->getBody($newsletter, $subscriber));
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => $this->auth()
      ),
      'body' => $body
    );
  }
}