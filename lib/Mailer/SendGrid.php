<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class SendGrid {
  function __construct($api_key, $from_email, $from_name, $newsletter, $subscribers) {
    $this->url = 'https://api.sendgrid.com/api/mail.send.json';
    $this->api_key = $api_key;
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;
    $this->from_name = $from_name;
    $this->from_email = $from_email;
  }

  function send() {
    if (!count($this->getSubscribers())) return false;
    $result = wp_remote_post(
      $this->url,
      $this->request()
    );
    $result = json_decode($result['body'], true);
    return (isset($result['errors']) === false);
  }

  function getSubscribers() {
    $subscribers = array_map(function ($subscriber) {
      if(!isset($subscriber['email'])) return;
      $first_name = (isset($subscriber['first_name'])) ? $subscriber['first_name'] : '';
      $last_name = (isset($subscriber['last_name'])) ? $subscriber['last_name'] : '';
      $subscriber = sprintf('%s %s <%s>', $first_name, $last_name, $subscriber['email']);
      $subscriber = trim(preg_replace('!\s\s+!', ' ', $subscriber));
      return $subscriber;
    }, $this->subscribers);
    return array_filter($subscribers);
  }

  function getBody() {
    $parameters = array(
      'from' => sprintf('%s <%s>', $this->from_name, $this->from_email),
      'x-smtpapi' => json_encode(array('to' => $this->getSubscribers())),
      'to' => $this->from_email,
      'subject' => $this->newsletter['subject'],
      'html' => $this->newsletter['body']
    );
    $body = array_map(function ($parameter, $value) {
      return $parameter . '=' . urlencode($value);
    }, array_keys($parameters), $parameters);
    return implode('&', array_filter($body));
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.1',
      'method' => 'POST',
      'headers' => array(
        'Authorization' => 'Bearer ' . $this->api_key,
        'Content-Type' => 'application/x-www-form-urlencoded'
      ),
      'body' => $this->getBody()
    );
  }
}