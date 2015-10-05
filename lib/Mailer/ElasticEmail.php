<?php
namespace MailPoet\Mailer;

if(!defined('ABSPATH')) exit;

class ElasticEmail {
  function __construct($api_key, $from_email, $from_name, $newsletter,
    $subscribers) {
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
      $this->request());
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    return (preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']) == true);
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
    return array_filter($subscribers);
  }

  function getBody() {
    $parameters = array(
      'api_key' => $this->api_key,
      'from' => $this->from_email,
      'from_name' => $this->from_name,
      'to' => implode(';', $this->getSubscribers()),
      'subject' => $this->newsletter['subject'],
      'body_html' => $this->newsletter['body']
    );
    return urldecode(http_build_query($parameters));
  }

  function request() {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'body' => $this->getBody()
    );
  }
}