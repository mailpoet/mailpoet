<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;

if(!defined('ABSPATH')) exit;

class MailPoet {
  public $api;
  public $sender;
  public $reply_to;
  public $services_checker;

  /** @var MailPoetMapper */
  private $error_mapper;

  function __construct($api_key, $sender, $reply_to, MailPoetMapper $error_mapper) {
    $this->api = new API($api_key);
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->services_checker = new ServicesChecker();
    $this->error_mapper = $error_mapper;
  }

  function send($newsletter, $subscriber, $extra_params = array()) {
    if($this->services_checker->isMailPoetAPIKeyValid() === false) {
      return Mailer::formatMailerErrorResult($this->error_mapper->getInvalidApiKeyError());
    }

    $message_body = $this->getBody($newsletter, $subscriber, $extra_params);
    $result = $this->api->sendMessages($message_body);

    switch($result['status']) {
      case API::SENDING_STATUS_CONNECTION_ERROR:
        $error = $this->error_mapper->getConnectionError($result['message']);
        return Mailer::formatMailerErrorResult($error);
      case API::SENDING_STATUS_SEND_ERROR:
        $error = $this->processSendError($result, $subscriber);
        return Mailer::formatMailerErrorResult($error);
      case API::SENDING_STATUS_OK:
      default:
        return Mailer::formatMailerSendSuccessResult();
    }
  }

  function processSendError($result, $subscriber) {
    if(!empty($result['code']) && $result['code'] ===  API::RESPONSE_CODE_KEY_INVALID) {
      Bridge::invalidateKey();
    }
    return $this->error_mapper->getErrorForResult($result, $subscriber);
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

  function getBody($newsletter, $subscriber, $extra_params = array()) {
    $_this = $this;
    $composeBody = function($newsletter, $subscriber, $unsubscribe_url) use($_this) {
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
      if($unsubscribe_url) {
        $body['list_unsubscribe'] = $unsubscribe_url;
      }
      return $body;
    };
    if(is_array($newsletter) && is_array($subscriber)) {
      $body = array();
      for($record = 0; $record < count($newsletter); $record++) {
        $body[] = $composeBody(
          $newsletter[$record],
          $this->processSubscriber($subscriber[$record]),
          (!empty($extra_params['unsubscribe_url'][$record])) ? $extra_params['unsubscribe_url'][$record] : false
        );
      }
    } else {
      $body[] = $composeBody(
        $newsletter,
        $this->processSubscriber($subscriber),
        (!empty($extra_params['unsubscribe_url'])) ? $extra_params['unsubscribe_url'] : false
      );
    }
    return $body;
  }
}
