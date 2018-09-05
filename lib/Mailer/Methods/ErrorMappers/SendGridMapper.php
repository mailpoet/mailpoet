<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\SubscriberError;

class SendGridMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromResponse($response, $subscriber, $extra_params) {
    $response = (!empty($response['errors'][0])) ?
      $response['errors'][0] :
      sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_SENDGRID);

    $subscriber_errors = [];
    if(empty($extra_params['test_email'])) {
      $subscriber_errors[] = new SubscriberError($subscriber, null);
    }
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $response, null, $subscriber_errors);
  }
}
