<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class MailPoet {
  function __construct($apiKey, $fromEmail, $fromName) {
    $this->url = 'https://bridge.mailpoet.com/api/messages';
    $this->apiKey = $apiKey;
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $this->processSubscriber($subscriber);
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    return (
      !is_wp_error($result) === true &&
      wp_remote_retrieve_response_code($result) === 201
    );
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriberData);
    if(!isset($subscriberData['email'])) {
      $subscriberData = array(
        'email' => $subscriber,
      );
    }
    return array(
      'email' => $subscriberData['email'],
      'name' => (isset($subscriberData['name'])) ? $subscriberData['name'] : ''
    );
  }

  function getBody() {
    return array(
      'to' => (array(
        'address' => $this->subscriber['email'],
        'name' => $this->subscriber['name']
      )),
      'from' => (array(
        'address' => $this->fromEmail,
        'name' => $this->fromName
      )),
      'subject' => $this->newsletter['subject'],
      'html' => $this->newsletter['body']['html'],
      'text' => $this->newsletter['body']['text']
    );
  }

  function auth() {
    return 'Basic ' . base64_encode('api:' . $this->apiKey);
  }

  function request() {
    $request = array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => $this->auth()
      ),
      'body' => json_encode(array($this->getBody()))
    );
    return $request;
  }
}