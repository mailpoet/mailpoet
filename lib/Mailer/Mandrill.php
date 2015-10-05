<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class Mandrill {
  function __construct($api_key, $from_email, $from_name, $newsletter,
    $subscribers) {
    $this->url = 'https://mandrillapp.com/api/1.0/messages/send.json';
    $this->api_key = $api_key;
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;
    $this->from_name = $from_name;
    $this->from_email = $from_email;
  }

  function send() {
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    return (!preg_match('!invalid!', $result['body']) === true && $result['response']['code'] === 200);
  }

  function getSubscribers() {
    $subscribers = array_map(function ($subscriber) {
      if(!isset($subscriber['email'])) return;
      $first_name = (isset($subscriber['first_name']))
        ? $subscriber['first_name'] : '';
      $last_name = (isset($subscriber['last_name']))
        ? $subscriber['last_name'] : '';
      $full_name = sprintf(
        '%s %s', $first_name, $last_name
      );
      $full_name = trim(preg_replace('!\s\s+!', ' ', $full_name));
      return array(
        'email' => $subscriber['email'],
        'name' => $full_name
      );
    }, $this->subscribers);
    return array_filter($subscribers);
  }

  function getBody() {
    return array(
      'key' => $this->api_key,
      'message' => array(
        'html' => $this->newsletter['body'],
        'subject' => $this->newsletter['subject'],
        'from_email' => $this->from_email,
        'from_name' => $this->from_name,
        'to' => $this->getSubscribers(),
        'headers' => array(
          'Reply-To' => $this->from_email
        ),
        'preserve_recipients' => false
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