<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Services\Bridge\API;

if(!defined('ABSPATH')) exit;

class MailPoetMapper {
  use ConnectionErrorMapperTrait;

  const TEMPORARY_UNAVAILABLE_RETRY_INTERVAL = 300; // seconds

  function getInvalidApiKeyError() {
    return new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_HARD,
      __('MailPoet API key is invalid!', 'mailpoet')
    );
  }

  function getErrorForResult(array $result, $subscribers) {
    $message = $result['message'];
    $level = MailerError::LEVEL_HARD;
    $retry_interval = null;

    if(!empty($result['code'])) {
      switch($result['code']) {
        case API::RESPONSE_CODE_NOT_ARRAY:
          $message = __('JSON input is not an array', 'mailpoet');
          break;
        case API::RESPONSE_CODE_PAYLOAD_ERROR:
          $message = $this->parseErrorResponse($result['message'], $subscribers);
          break;
        case API::RESPONSE_CODE_TEMPORARY_UNAVAILABLE:
          $message = __('Email service is temporarily not available, please try again in a few minutes.', 'mailpoet');
          $retry_interval = self::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL;
          break;
        case API::RESPONSE_CODE_KEY_INVALID:
        case API::RESPONSE_CODE_PAYLOAD_TOO_BIG:
        default:
          $message = $result['message'];
      }
    }
    return new MailerError(MailerError::OPERATION_SEND, $level, $message, $retry_interval);
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
