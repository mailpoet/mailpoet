<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\SubscriberError;

class AmazonSESMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromException(\Exception $e) {
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $e->getMessage());
  }

  function getErrorFromResponse($response, $subscriber, $extra_params) {
    $response = ($response) ?
      $response->Error->Message->__toString() :
      sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_AMAZONSES);

    $subscriber_errors = [];
    if(empty($extra_params['test_email'])) {
      $subscriber_errors[] = new SubscriberError($subscriber, null);
    }
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $response, null, $subscriber_errors);
  }
}
