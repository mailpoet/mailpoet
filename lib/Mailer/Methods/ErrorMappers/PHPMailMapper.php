<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\SubscriberError;

class PHPMailMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromException(\Exception $e) {
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $e->getMessage());
  }

  function getErrorForSubscriber($subscriber, $extra_params) {
    $message = sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_PHPMAIL);
    $subscriber_errors = [];
    if(empty($extra_params['test_email'])) {
      $subscriber_errors[] = new SubscriberError($subscriber, null);
    }
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message, null, $subscriber_errors);
  }
}
