<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class MailPoet {
  public $url = 'https://bridge.mailpoet.com/api/v0/messages';
  public $api_key;
  public $sender;
  public $reply_to;
  public $services_checker;

  function __construct($api_key, $sender, $reply_to) {
    $this->api_key = $api_key;
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->services_checker = new ServicesChecker(false);
  }

  function send($newsletter, $subscriber, $extra_params = array()) {
    if($this->services_checker->checkMailPoetAPIKeyValid() === false) {
      $response = __('MailPoet API key is invalid!', 'mailpoet');
      return Mailer::formatMailerSendErrorResult($response);
    }
    $message_body = $this->getBody($newsletter, $subscriber);
    $result = wp_remote_post(
      $this->url,
      $this->request($message_body)
    );
    if(is_wp_error($result)) {
      return Mailer::formatMailerConnectionErrorResult($result->get_error_message());
    }
    $response_code = wp_remote_retrieve_response_code($result);
    if($response_code !== 201) {
      if($response_code === 401) {
        Bridge::invalidateKey();
      }
      $response = (wp_remote_retrieve_body($result)) ?
        wp_remote_retrieve_body($result) :
        wp_remote_retrieve_response_message($result);
      return Mailer::formatMailerSendErrorResult($response);
    }
    return Mailer::formatMailerSendSuccessResult();
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if(!isset($subscriber_data['email'])) {
      $subscriber_data = array(
        'email' => $subscriber,
      );
    }
    return array(
      'email' => $subscriber_data['email'],
      'name' => (isset($subscriber_data['name'])) ? $subscriber_data['name'] : ''
    );
  }

  function getBody($newsletter, $subscriber) {
    $composeBody = function($newsletter, $subscriber) {
      $body = array(
        'to' => (array(
          'address' => $subscriber['email'],
          'name' => $subscriber['name']
        )),
        'from' => (array(
          'address' => $this->sender['from_email'],
          'name' => $this->sender['from_name']
        )),
        'reply_to' => (array(
          'address' => $this->reply_to['reply_to_email'],
          'name' => $this->reply_to['reply_to_name']
        )),
        'subject' => $newsletter['subject']
      );
      if(!empty($newsletter['body']['html'])) {
        $body['html'] = $newsletter['body']['html'];
      }
      if(!empty($newsletter['body']['text'])) {
        $body['text'] = $newsletter['body']['text'];
      }
      return $body;
    };
    if(is_array($newsletter) && is_array($subscriber)) {
      $body = array();
      for($record = 0; $record < count($newsletter); $record++) {
        $body[] = $composeBody(
          $newsletter[$record],
          $this->processSubscriber($subscriber[$record])
        );
      }
    } else {
      $body[] = $composeBody($newsletter, $this->processSubscriber($subscriber));
    }
    return $body;
  }

  function auth() {
    return 'Basic ' . base64_encode('api:' . $this->api_key);
  }

  function request($body) {
    return array(
      'timeout' => 10,
      'httpversion' => '1.0',
      'method' => 'POST',
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => $this->auth()
      ),
      'body' => json_encode($body)
    );
  }
}