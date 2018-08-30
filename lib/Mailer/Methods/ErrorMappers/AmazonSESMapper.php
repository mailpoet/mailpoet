<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Mailer;

class AmazonSESMapper {
  use ConnectionErrorMapperTrait;

  function getErrorFromException(\Exception $e) {
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $e->getMessage());
  }

  function getErrorFromResponse($response, $subscriber, $extra_params) {
    $response = ($response) ?
      $response->Error->Message->__toString() :
      sprintf(__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_AMAZONSES);
    if(empty($extra_params['test_email'])) {
      $response .= sprintf(' %s: %s', __('Unprocessed subscriber', 'mailpoet'), $subscriber);
    }
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $response);
  }
}
