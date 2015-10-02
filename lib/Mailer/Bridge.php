<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Bridge {
  protected $from_address = null;
  protected $from_name = '';
  protected $reply_to_address = null;
  protected $reply_to_name = '';
  protected $newsletter = null;
  protected $subscribers = null;
  protected $api_key = null;

  function __construct($newsletter, $subscribers) {
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;

    $this->from_address = (
        isset($this->newsletter['from_address'])
      )
      ? $this->newsletter['from_address']
      : Setting::getValue('from_address');

    $this->from_name = (
        isset($this->newsletter['from_name'])
      )
      ? $this->newsletter['from_name']
      : Setting::getValue('from_name', '');

    $this->reply_to_address = (
        isset($this->newsletter['reply_to_address'])
      )
      ? $this->newsletter['reply_to_address']
      : Setting::getValue('reply_to_address');

    $this->reply_to_name = (
        isset($this->newsletter['reply_to_name'])
      )
      ? $this->newsletter['reply_to_name']
      : Setting::getValue('reply_to_name', '');

    $this->api_key = Setting::where('name', 'api_key')->findOne()->value;
  }

  function messages() {
    $messages = array_map(
      array($this, 'generateMessage'),
      $this->subscribers
    );
    return $messages;
  }

  function generateMessage($subscriber) {
    $message = array(
      'subject' => $this->newsletter['subject'],
      'to' => array(
        'address' => $subscriber['email'],
        'name' => $subscriber['first_name'].' '.$subscriber['last_name']
      ),
      'from' => array(
        'address' => $this->from_address,
        'name' => $this->from_name
      ),
      'text' => "",
      'html' => $this->newsletter['body']
    );

    if($this->reply_to_address !== null) {
      $message['reply_to'] = array(
        'address' => $this->reply_to_address,
        'name' => $this->reply_to_name
      );
    }
    return $message;
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
      (wp_remote_retrieve_response_code($result) === 201);

    return $success;
  }
}
