<?php
namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\WP\Functions as WPFunctions;

trait BlacklistErrorMapperTrait {
  function getBlacklistError($subscriber) {
    $message = sprintf(WPFunctions::get()->__('%s has returned an unknown error.', 'mailpoet'), self::METHOD);
    $subscriber_errors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, MailerError::LEVEL_SOFT, $message, null, $subscriber_errors);
  }
}
