<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\SubscriberError;

class SendGridMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromResponse($response, $subscriber) {
    $response = (!empty($response['errors'][0])) ?
      $response['errors'][0] :
      sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_SENDGRID);

    $level = MailerError::LEVEL_HARD;
    if(strpos($response, 'Invalid email address') === 0) {
      $level = MailerError::LEVEL_SOFT;
    }
    $subscriber_errors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, $level, $response, null, $subscriber_errors);
  }
}
