<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;

if(!defined('ABSPATH')) exit;

class MailPoet {

  const TEMPORARY_UNAVAILABLE_RETRY_INTERVAL = 300; // seconds

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

    $message_body = $this->getBody($newsletter, $subscriber, $extra_params);
    $result = $this->api->sendMessages($message_body);

    switch($result['status']) {
      case API::SENDING_STATUS_CONNECTION_ERROR:
        return Mailer::formatMailerConnectionErrorResult($result['message']);
      case API::SENDING_STATUS_SEND_ERROR:
        return $this->processSendError($result, $subscriber);
      case API::SENDING_STATUS_OK:
      default:
        return Mailer::formatMailerSendSuccessResult();
    }
  }

  function processSendError($result, $subscriber) {
    if(!empty($result['code'])) {
      switch($result['code']) {
        case API::RESPONSE_CODE_NOT_ARRAY:
          return Mailer::formatMailerSendErrorResult(__('JSON input is not an array', 'mailpoet'));
        case API::RESPONSE_CODE_PAYLOAD_TOO_BIG:
          return Mailer::formatMailerSendErrorResult($result['message']);
        case API::RESPONSE_CODE_PAYLOAD_ERROR:
          $error = $this->parseErrorResponse($result['message'], $subscriber);
          return Mailer::formatMailerSendErrorResult($error);
        case API::RESPONSE_CODE_TEMPORARY_UNAVAILABLE:
          $error = Mailer::formatMailerSendErrorResult(__('Email service is temporarily not available, please try again in a few minutes.', 'mailpoet'));
          $error['retry_interval'] = self::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL;
          return $error;
        case API::RESPONSE_CODE_KEY_INVALID:
          Bridge::invalidateKey();
          break;
        default:
          return Mailer::formatMailerSendErrorResult($result['message']);
      }
    }
    return Mailer::formatMailerSendErrorResult($result['message']);
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

  private function parseErrorResponse($result, $subscriber) {
    $result_parsed = json_decode($result, true);
    $errors = [];
    if(is_array($result_parsed)) {
      foreach($result_parsed as $result_error) {
        $errors[] = $this->processSingleSubscriberError($result_error, $subscriber);
      }
    }
    if(!empty($errors)) {
      return __('Error while sending: ', 'mailpoet') . join(', ', $errors);
    } else {
      return __('Error while sending newsletters. ', 'mailpoet') . $result;
    }
  }

  private function processSingleSubscriberError($result_error, $subscriber) {
    $error = '';
    if(is_array($result_error)) {
      $subscriber_errors = [];
      if(isset($result_error['errors']) && is_array($result_error['errors'])) {
        array_walk_recursive($result_error['errors'], function($item) use (&$subscriber_errors) {
          $subscriber_errors[] = $item;
        });
      }
      $error .= join(', ', $subscriber_errors);
      
      if(isset($result_error['index']) && isset($subscriber[$result_error['index']])) {
        $error = '(' . $subscriber[$result_error['index']] . ': ' . $error . ')';
      }
    }
    return $error;
  }
}
