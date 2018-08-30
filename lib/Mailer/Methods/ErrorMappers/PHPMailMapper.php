<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;

class PHPMailMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromException(\Exception $e) {
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $e->getMessage());
  }

  function getErrorForSubscriber($subscriber, $extra_params) {
    $message = sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_PHPMAIL);
    if(empty($extra_params['test_email'])) {
      $message .= sprintf(' %s: %s', __('Unprocessed subscriber', 'mailpoet'), $subscriber);
    }
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message);
  }
}
