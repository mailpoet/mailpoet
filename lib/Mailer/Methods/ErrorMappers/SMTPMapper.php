<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\SubscriberError;
use MailPoet\WP\Functions as WPFunctions;

class SMTPMapper {
  use BlacklistErrorMapperTrait;
  use ConnectionErrorMapperTrait;

  const METHOD = Mailer::METHOD_SMTP;

  /**
   * @see https://swiftmailer.symfony.com/docs/sending.html
   * @return MailerError
   */
  function getErrorFromException(\Exception $e, $subscriber) {
    // remove redundant information appended by Swift logger to exception messages
    $message = explode(PHP_EOL, $e->getMessage());

    $level = MailerError::LEVEL_HARD;
    if ($e instanceof \Swift_RfcComplianceException) {
      $level = MailerError::LEVEL_SOFT;
    }
    $subscriber_errors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, $level, $message[0], null, $subscriber_errors);
  }

  function getErrorFromLog($log, $subscriber) {
    // extract error message from log
    preg_match('/!! (.*?)>>/ism', $log, $message);
    if (!empty($message[1])) {
      $message = $message[1];
      // remove line breaks from the message due to how logger's dump() method works
      $message = preg_replace('/\r|\n/', '', $message);
    } else {
      $message = sprintf(WPFunctions::get()->__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_SMTP);
    }
    $subscriber_errors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message, null, $subscriber_errors);
  }
}
