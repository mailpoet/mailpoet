<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class ElasticEmail {
  function __construct($api_key, $from_email, $from_name, $newsletter, $subscribers) {
    $this->url = 'https://api.elasticemail.com/mailer/send';
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
    return preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']);
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
    return implode(';', array_filter($subscribers));
  }

  function getBody() {
    $parameters = array(
      'api_key' => $this->api_key,
      'from' => $this->from_email,
      'from_name' => $this->from_name,
      'to' => $this->getSubscribers(),
      'subject' => $this->newsletter['subject'],
      'body_html' => $this->newsletter['body']
    );

    $body = array_map(function ($parameter, $value) {
      return $parameter . '=' . urlencode($value);
    }, array_keys($parameters), $parameters);
    return implode('&', $body);
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded'
      ),
      'body' => $this->getBody()
    );
  }
}