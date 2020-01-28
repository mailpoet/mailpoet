<?php

namespace MailPoet\Mailer\Methods\ErrorMappers;

use InvalidArgumentException;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Services\Bridge\API;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class MailPoetMapper {
  use BlacklistErrorMapperTrait;
  use ConnectionErrorMapperTrait;

  const METHOD = Mailer::METHOD_MAILPOET;

  const TEMPORARY_UNAVAILABLE_RETRY_INTERVAL = 300; // seconds

  public function getInvalidApiKeyError() {
    return new MailerError(
      MailerError::OPERATION_SEND,
      MailerError::LEVEL_HARD,
      WPFunctions::get()->__('MailPoet API key is invalid!', 'mailpoet')
    );
  }

  public function getErrorForResult(array $result, $subscribers, $sender = null, $newsletter = null) {
    $level = MailerError::LEVEL_HARD;
    $operation = MailerError::OPERATION_SEND;
    $retryInterval = null;
    $subscribersErrors = [];
    $resultCode = !empty($result['code']) ? $result['code'] : null;

    switch ($resultCode) {
      case API::RESPONSE_CODE_NOT_ARRAY:
        $message = WPFunctions::get()->__('JSON input is not an array', 'mailpoet');
        break;
      case API::RESPONSE_CODE_PAYLOAD_ERROR:
        $resultParsed = json_decode($result['message'], true);
        $message = WPFunctions::get()->__('Error while sending.', 'mailpoet');
        if (!is_array($resultParsed)) {
          $message .= ' ' . $result['message'];
          break;
        }
        try {
          $subscribersErrors = $this->getSubscribersErrors($resultParsed, $subscribers);
          $level = MailerError::LEVEL_SOFT;
        } catch (InvalidArgumentException $e) {
          $message .= ' ' . $e->getMessage();
        }
        break;
      case API::RESPONSE_CODE_TEMPORARY_UNAVAILABLE:
        $message = WPFunctions::get()->__('Email service is temporarily not available, please try again in a few minutes.', 'mailpoet');
        $retryInterval = self::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL;
        break;
      case API::RESPONSE_CODE_CAN_NOT_SEND:
        if ($result['message'] === MailerError::MESSAGE_EMAIL_NOT_AUTHORIZED) {
          $operation = MailerError::OPERATION_AUTHORIZATION;
          $message = $this->getUnauthorizedEmailMessage($sender);
        } else {
          $message = $this->getAccountBannedMessage();
        }
        break;
      case API::RESPONSE_CODE_KEY_INVALID:
      case API::RESPONSE_CODE_PAYLOAD_TOO_BIG:
      default:
        $message = $result['message'];
    }
    return new MailerError($operation, $level, $message, $retryInterval, $subscribersErrors);
  }

  private function getSubscribersErrors($resultParsed, $subscribers) {
    $errors = [];
    foreach ($resultParsed as $resultError) {
      if (!is_array($resultError) || !isset($resultError['index']) || !isset($subscribers[$resultError['index']])) {
        throw new InvalidArgumentException( WPFunctions::get()->__('Invalid MSS response format.', 'mailpoet'));
      }
      $subscriberErrors = [];
      if (isset($resultError['errors']) && is_array($resultError['errors'])) {
        array_walk_recursive($resultError['errors'], function($item) use (&$subscriberErrors) {
          $subscriberErrors[] = $item;
        });
      }
      $message = join(', ', $subscriberErrors);
      $errors[] = new SubscriberError($subscribers[$resultError['index']], $message);
    }
    return $errors;
  }

  private function getUnauthorizedEmailMessage($sender) {
    $email = $sender ? $sender['from_email'] : null;
    $message = '<p>';
    $message .= sprintf(WPFunctions::get()->__('The MailPoet Sending Service did not send your latest email because the address %s is not yet authorized.', 'mailpoet'), '<i>' . ( $email ?: WPFunctions::get()->__('Unknown address') ) . '</i>' );
    $message .= '</p><p>';
    $message .= Helpers::replaceLinkTags(
      WPFunctions::get()->__('[link]Authorize your email in your account now.[/link]', 'mailpoet'),
      'https://account.mailpoet.com/authorization',
      [
        'class' => 'button button-primary',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ]
    );
    $message .= ' &nbsp; <button class="button mailpoet-js-button-resume-sending">' . WPFunctions::get()->__('Resume sending', 'mailpoet') . '</button>';
    $message .= '</p>';
    return $message;
  }

  private function getAccountBannedMessage() {
    $message = WPFunctions::get()->__('The MailPoet Sending Service has stopped sending your emails for one of the following reasons:', 'mailpoet');

    $subscriberLimitMessage = Helpers::replaceLinkTags(
      WPFunctions::get()->__('You may have reached the subscriber limit of your plan. [link]Manage your subscriptions[/link].', 'mailpoet'),
      'https://account.mailpoet.com/account',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ]
    );

    $deliverabilityMessage = Helpers::replaceLinkTags(
      WPFunctions::get()->__('You may have had a poor deliverability rate. Please [link]contact our support team[/link] to resolve the issue.', 'mailpoet'),
      'https://www.mailpoet.com/support/',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ]
    );

    return "$message<br><br>$subscriberLimitMessage<br>$deliverabilityMessage<br>";
  }
}
