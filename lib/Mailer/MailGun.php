<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class MailGun {
  function __construct($domain, $api_key, $from_email, $from_name,
    $newsletter, $subscribers) {
    $this->url = 'https://api.mailgun.net/v3';
    $this->domain = $domain;
    $this->api_key = $api_key;
    $this->newsletter = $newsletter;
    $this->subscribers = $subscribers;
    $this->from = sprintf('%s <%s>', $from_name, $from_email);
  }

  function send() {
    $result = wp_remote_post(
      $this->url . '/' . $this->domain . '/messages',
      $this->request()
    );
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    return ($result['response']['code'] === 200);
  }

  function getSubscribers() {
    $subscribers = array_map(function ($subscriber) {
      if(!isset($subscriber['email'])) return;
      $first_name = (isset($subscriber['first_name']))
        ? $subscriber['first_name'] : '';
      $last_name = (isset($subscriber['last_name']))
        ? $subscriber['last_name'] : '';
      $subscriber = sprintf(
        '%s %s <%s>', $first_name, $last_name, $subscriber['email']
      );
      $subscriber = trim(preg_replace('!\s\s+!', ' ', $subscriber));
      return $subscriber;
    }, $this->subscribers);
    $subscribers = array_filter($subscribers);

    $subscribersData = array_map(function ($subscriber) {
      return array($subscriber => array());
    }, $subscribers);
    $subscribersData = array_map('array_merge', $subscribersData);

    return array(
      'emails' => $subscribers,
      'data' => $subscribersData
    );
  }

  function getBody() {
    $subscribers = $this->getSubscribers();
    $parameters = array(
      'from' => $this->from,
      'to' => $subscribers['emails'],
      'recipient-variables' => json_encode($subscribers['data']),
      'subject' => $this->newsletter['subject'],
      'text' => $this->newsletter['body']
    );
    $parameters = http_build_query($parameters);
    $parameters = preg_replace('!\[\d+\]!', '', urldecode($parameters));
    return $parameters;
  }

  function auth() {
    return 'Basic ' . base64_encode('api:' . $this->api_key);
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Authorization' => $this->auth()
      ),
      'body' => $this->getBody()
    );
  }
}