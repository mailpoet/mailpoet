<?php
namespace MailPoet\Mailer\API;

if(!defined('ABSPATH')) exit;

class ElasticEmail {
  function __construct($api_key, $from_email, $from_name) {
    $this->url = 'https://api.elasticemail.com/mailer/send';
    $this->api_key = $api_key;
    $this->from_name = $from_name;
    $this->from_email = $from_email;
  }

  function send($newsletter, $subscriber) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $result = wp_remote_post(
      $this->url,
      $this->request());
    if(is_object($result) && get_class($result) === 'WP_Error') return false;
    return (!preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $result['body']) === false);
  }

  function getBody() {
    $parameters = array(
      'api_key' => $this->api_key,
      'from' => $this->from_email,
      'from_name' => $this->from_name,
      'to' => $this->subscriber,
      'subject' => $this->newsletter['subject'],
      'body_html' => $this->newsletter['body']['html'],
      'body_text' => $this->newsletter['body']['text']
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