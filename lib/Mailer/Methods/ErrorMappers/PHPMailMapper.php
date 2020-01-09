<?php

namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\WP\Functions as WPFunctions;

class PHPMailMapper {
  use BlacklistErrorMapperTrait;
  use ConnectionErrorMapperTrait;

  const METHOD = Mailer::METHOD_PHPMAIL;

  public function getErrorFromException(\Exception $e, $subscriber) {
    $level = MailerError::LEVEL_HARD;
    if (strpos($e->getMessage(), 'Invalid address') === 0) {
      $level = MailerError::LEVEL_SOFT;
    }

    $subscriberErrors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, $level, $e->getMessage(), null, $subscriberErrors);
  }

  public function getErrorForSubscriber($subscriber) {
    $message = sprintf(WPFunctions::get()->__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_PHPMAIL);
    $subscriberErrors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_HARD, $message, null, $subscriberErrors);
  }
}
