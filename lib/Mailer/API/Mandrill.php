<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class Mandrill {
  function __construct($api_key, $from_email, $from_name) {
    $this->url = 'https://mandrillapp.com/api/1.0/messages/send.json';
    $this->api_key = $api_key;
    $this->from_name = $from_name;
    $this->from_email = $from_email;
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $this->processSubscriber($subscriber);
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    return (!preg_match('!invalid!', $result['body']) === true && $result['response']['code'] === 200);
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriberData);
    if(!isset($subscriberData['email'])) {
      $subscriberData = array(
        'email' => $subscriber,
      );
    }
    return array(
      array(
        'name' => (isset($subscriberData['name'])) ? $subscriberData['name'] : '',
        'email' => $subscriberData['email']
      )
    );
  }

  function getBody() {
    return array(
      'key' => $this->api_key,
      'message' => array(
        'from_email' => $this->from_email,
        'from_name' => $this->from_name,
        'to' => $this->subscriber,
        'subject' => $this->newsletter['subject'],
        'html' => $this->newsletter['body']['html'],
        'text' => $this->newsletter['body']['text'],
        'headers' => array(
          'Reply-To' => $this->from_email
        )
      ),
      'async' => false,
    );
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json'
      ),
      'body' => json_encode($this->getBody())
    );
  }
}