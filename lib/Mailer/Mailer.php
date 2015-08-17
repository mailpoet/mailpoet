<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

// TODO: remove
define('MAILPOET_BRIDGE_KEY', 'Xc_1zr7aOxqfD5s5xZqGvnvUJ7yc6HBvGBxk4PD3V2U');

class Mailer {

  protected $newsletter;
  protected $subscribers;

  function __construct(array $newsletter, array $subscribers) {
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;
    $this->fromAddress = 'info@mailpoet.com';
    $this->replyToAddress = 'info@mailpoet.com';
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
      'from' => (array(
        'address' => $this->fromAddress,
        'name' => ''
      )),
      'to' => (array(
        'address' => $subscriber['email'],
        'name' => $subscriber['first_name'].' '.$subscriber['last_name']
      )),
      'reply_to' => (array(
        'address' => $this->replyToAddress,
        'name' => ''
      )),
      'subject' => $this->newsletter['subject'],
      'html' => $this->newsletter['body'],
      'text' => ""
    );
  }

  function auth() {
    $auth = 'Basic ' . base64_encode('api:' . MAILPOET_BRIDGE_KEY);
    return $auth;
  }

  function request() {
    $request = array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'headers' => array(
        'method' => 'POST',
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
    $success = wp_remote_retrieve_response_code($result) === 201;
    return $success;
  }
}
