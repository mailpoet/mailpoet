<?php

namespace MailPoet\Mailer\Methods\ErrorMappers;

use InvalidArgumentException;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Services\Bridge\API;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\UnauthorizedEmailNotice;
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
      __('MailPoet API key is invalid!', 'mailpoet')
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
        $message = __('JSON input is not an array', 'mailpoet');
        break;
      case API::RESPONSE_CODE_PAYLOAD_ERROR:
        $resultParsed = json_decode($result['message'], true);
        $message = __('Error while sending.', 'mailpoet');
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
        $message = __('Email service is temporarily not available, please try again in a few minutes.', 'mailpoet');
        $retryInterval = self::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL;
        break;
      case API::RESPONSE_CODE_CAN_NOT_SEND:
        if ($result['message'] === MailerError::MESSAGE_EMAIL_INSUFFICIENT_PRIVILEGES) {
          $operation = MailerError::OPERATION_INSUFFICIENT_PRIVILEGES;
          $message = $this->getInsufficientPrivilegesMessage();
        } elseif ($result['message'] === MailerError::MESSAGE_EMAIL_NOT_AUTHORIZED) {
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
        throw new InvalidArgumentException(__('Invalid MSS response format.', 'mailpoet'));
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
    $email = $sender ? $sender['from_email'] : __('Unknown address', 'mailpoet');
    $validationError = ['invalid_sender_address' => $email];
    $notice = new UnauthorizedEmailNotice(WPFunctions::get(), null);
    $message = $notice->getMessage($validationError);
    return $message;
  }

  private function getInsufficientPrivilegesMessage(): string {
    $message = __('You have reached the subscriber limit of your plan. Please [link1]upgrade your plan[/link1], or [link2]contact our support team[/link2] if you have any questions.', 'mailpoet');
    $message = Helpers::replaceLinkTags(
      $message,
      'https://account.mailpoet.com/account/',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
      'link1'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://www.mailpoet.com/support/',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
      'link2'
    );

    return "{$message}<br/>";
  }

  private function getAccountBannedMessage(): string {
    $message = __('MailPoet Sending Service has been temporarily suspended for your site due to [link1]degraded email deliverability[/link1]. Please [link2]contact our support team[/link2] to resolve the issue.', 'mailpoet');
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/231-sending-does-not-work#suspended',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
      'link1'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://www.mailpoet.com/support/',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
      'link2'
    );

    return "{$message}<br/>";
  }
}
