<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;

if(!defined('ABSPATH')) exit;

class MailPoet {
  public $api;
  public $sender;
  public $reply_to;
  public $services_checker;

  function __construct($api_key, $sender, $reply_to) {
    $this->api = new API($api_key);
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->services_checker = new ServicesChecker(false);
  }

  function send($newsletter, $subscriber, $extra_params = array()) {
    if($this->services_checker->isMailPoetAPIKeyValid() === false) {
      $response = __('MailPoet API key is invalid!', 'mailpoet');
      return Mailer::formatMailerSendErrorResult($response);
    }

    $message_body = $this->getBody($newsletter, $subscriber);
    $result = $this->api->sendMessages($message_body);

    switch($result['status']) {
      case API::SENDING_STATUS_CONNECTION_ERROR:
        return Mailer::formatMailerConnectionErrorResult($result['message']);
      case API::SENDING_STATUS_SEND_ERROR:
        if(!empty($result['code']) && $result['code'] === API::RESPONSE_CODE_KEY_INVALID) {
          Bridge::invalidateKey();
        }
        return Mailer::formatMailerSendErrorResult($result['message']);
      case API::SENDING_STATUS_OK:
      default:
        return Mailer::formatMailerSendSuccessResult();
    }
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
    $_this = $this;
    $composeBody = function($newsletter, $subscriber) use($_this) {
      $body = array(
        'to' => (array(
          'address' => $subscriber['email'],
          'name' => $subscriber['name']
        )),
        'from' => (array(
          'address' => $_this->sender['from_email'],
          'name' => $_this->sender['from_name']
        )),
        'reply_to' => (array(
          'address' => $_this->reply_to['reply_to_email'],
          'name' => $_this->reply_to['reply_to_name']
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
}