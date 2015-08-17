<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

// TODO: remove
define('MAILPOET_BRIDGE_KEY', 'Xc_1zr7aOxqfD5s5xZqGvnvUJ7yc6HBvGBxk4PD3V2U');

class Mailer {

  protected $newsletter;
  protected $subscribers;
  protected $errors = array();

  function __construct(array $newsletter, array $subscribers) {
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;
    $this->fromAddress = 'marco@mailpoet.com';
    $this->replyToAddress = 'staff@mailpoet.com';
  }

  protected function generateMessage(array $subscriber) {
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
  function send() {
    $messages = array_map(
      array($this, 'generateMessage'),
      $this->subscribers
    );
    $result = wp_remote_post(
      'https://bridge.mailpoet.com/api/messages', array(
        'timeout' => 10,
        'httpversion' => '1.0',
        // 'sslverify' => false,
        'headers' => array(
          'method' => 'POST',
          'Authorization' => 'Basic ' . base64_encode('api:'.MAILPOET_BRIDGE_KEY),
          'Content-Type' => 'application/json'
        ),
        'body' => json_encode($messages)
      )
    );
    return (wp_remote_retrieve_response_code($result) === 201);
  }

}
