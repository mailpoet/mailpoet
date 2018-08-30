<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;

class SMTPMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromException(\Exception $e) {
    // remove redundant information appended by Swift logger to exception messages
    $message = explode(PHP_EOL, $e->getMessage());
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message[0]);
  }

  function getErrorFromLog($log, $subscriber, $extra_params = []) {
    // extract error message from log
    preg_match('/!! (.*?)>>/ism', $log, $message);
    if(!empty($message[1])) {
      $message = $message[1];
      // remove line breaks from the message due to how logger's dump() method works
      $message = preg_replace('/\r|\n/', '', $message);
    } else {
      $message = sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_SMTP);
    }
    if(empty($extra_params['test_email'])) {
      $message .= sprintf(' %s: %s', __('Unprocessed subscriber', 'mailpoet'), $subscriber);
    }
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message);
  }
}
