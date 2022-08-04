<?php

namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;

trait BlacklistErrorMapperTrait {
  public function getBlacklistError($subscriber) {
    // translators: %s is the name of the method.
    $message = sprintf(__('%s has returned an unknown error.', 'mailpoet'), self::METHOD);
    $subscriberErrors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_SOFT, $message, null, $subscriberErrors);
  }
}
