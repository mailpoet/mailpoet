<?php
namespace MailPoet\Mailer\Methods;

if(!defined('ABSPATH')) exit;

class MailPoet {
  public $url = 'https://bridge.mailpoet.com/api/messages';
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
        'address' => $this->from_email,
        'name' => $this->from_name
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