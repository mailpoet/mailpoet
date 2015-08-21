<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Bridge {
  function __construct($newsletter, $subscribers) {
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;

    $this->from_name =
      Setting::where('name', 'from_name')
      ->findOne()->value;

    $this->from_address =
      Setting::where('name', 'from_address')
      ->findOne()->value;

    $this->api_key =
      Setting::where('name', 'api_key')
      ->findOne()->value;
  }

  function messages() {
    $messages = array_map(
      array($this, 'generateMessage'),
      $this->subscribers
    );
    return $messages;
  }

  function generateMessage($subscriber) {
    return array(
      'subject' => $this->newsletter['subject'],
      'to' => (array(
        'address' => $subscriber['email'],
        'name' => $subscriber['first_name'].' '.$subscriber['last_name']
      )),
      'from' => (array(
        'address' => $this->from_address,
        'name' => $this->from_name
      )),
      'text' => "",
      'html' => $this->newsletter['body']
    );
  }

  function auth() {
    $auth = 'Basic '
      . base64_encode('api:' . $this->api_key);
    return $auth;
  }

  function request() {
    $request = array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Authorization' => $this->auth(),
        'Content-Type' => 'application/json'
      ),
      'body' => json_encode($this->messages())
    );
    return $request;
  }

  function send() {
    $result = wp_remote_post(
      'https://bridge.mailpoet.com/api/messages',
      $this->request()
    );

    $success =
      (wp_remote_retrieve_response_code($result)===201);

    return $success;
  }
}
