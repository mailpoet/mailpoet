<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class MailPoet {
  function __construct($apiKey, $fromEmail, $fromName) {
    $this->url = 'https://bridge.mailpoet.com/api/messages';
    $this->apiKey = $apiKey;
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
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

  function getBody($newsletter, $subscriber) {
    $body = array(
      'to' => (array(
        'address' => $subscriber['email'],
        'name' => $subscriber['name']
      )),
      'from' => (array(
        'address' => $this->fromEmail,
        'name' => $this->fromName
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
    return 'Basic ' . base64_encode('api:' . $this->apiKey);
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