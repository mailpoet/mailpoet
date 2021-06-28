<?php

namespace MailPoet\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Swift_RfcComplianceException;

class AmazonSESMapper {
  use BlacklistErrorMapperTrait;
  use ConnectionErrorMapperTrait;

  const METHOD = Mailer::METHOD_AMAZONSES;

  public function getErrorFromException(\Exception $e, $subscriber) {
    $level = MailerError::LEVEL_HARD;
    if ($e instanceof Swift_RfcComplianceException) {
      $level = MailerError::LEVEL_SOFT;
    }
    $subscriberErrors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, $level, $e->getMessage(), null, $subscriberErrors);
  }

  /**
   * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/api-error-codes.html
   * @return MailerError
   */
  public function getErrorFromResponse($response, $subscriber) {
    $message = ($response) ?
      $response->Error->Message->__toString() : // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      sprintf(WPFunctions::get()->__('%s has returned an unknown error.', 'mailpoet'), Mailer::METHOD_AMAZONSES);

    $level = MailerError::LEVEL_HARD;
    if ($response && $response->Error->Code->__toString() === 'MessageRejected') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $level = MailerError::LEVEL_SOFT;
    }
    $subscriberErrors = [new SubscriberError($subscriber, null)];
    return new MailerError(MailerError::OPERATION_SEND, $level, $message, null, $subscriberErrors);
  }
}
