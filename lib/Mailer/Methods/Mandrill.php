<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class Mandrill {
  public $url = 'https://mandrillapp.com/api/1.0/messages/send.json';
  public $api_key;
  public $from_email;
  public $from_name;
  
  function __construct($api_key, $from_email, $from_name) {
    $this->api_key = $api_key;
    $this->from_name = $from_name;
    $this->from_email = $from_email;
  }
  
  function send($newsletter, $subscriber) {
    $result = wp_remote_post(
      $this->url,
      $this->request($newsletter, $this->processSubscriber($subscriber))
    );
    return (
      !is_wp_error($result) === true &&
      !preg_match('!invalid!', $result['body']) === true &&
      wp_remote_retrieve_response_code($result) === 200
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
      'key' => $this->api_key,
      'message' => array(
        'from_email' => $this->from_email,
        'from_name' => $this->from_name,
        'to' => array($subscriber),
        'subject' => $newsletter['subject']
      ),
      'async' => false,
    );
    if(!empty($newsletter['body']['html'])) {
      $body['message']['html'] = $newsletter['body']['html'];
    }
    if(!empty($newsletter['body']['text'])) {
      $body['message']['text'] = $newsletter['body']['text'];
    }
    return $body;
  }
  
  function request($newsletter, $subscriber) {
    $body = $this->getBody($newsletter, $subscriber);
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json'
      ),
      'body' => json_encode($body)
    );
  }
}