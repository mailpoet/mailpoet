<?php

namespace MailPoet\Mailer\Methods\ErrorMappers;

use InvalidArgumentException;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\SubscriberError;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\Util\Notices\UnauthorizedEmailNotice;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class MailPoetMapper {
  use BlacklistErrorMapperTrait;
  use ConnectionErrorMapperTrait;

  const METHOD = Mailer::METHOD_MAILPOET;

  const TEMPORARY_UNAVAILABLE_RETRY_INTERVAL = 300; // seconds
  // Bridge message from https://github.com/mailpoet/services-bridge/blob/a3fbf0c1a88abc77840f9ec9f3965e632ce7d8b5/api/messages.rb#L16
  const MAILPOET_BRIDGE_DMRAC_ERROR = "Email violates Sender Domain's DMARC policy. Please set up sender authentication.";

  /** @var Bridge */
  private $bridge;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    Bridge $bridge,
    ServicesChecker $servicesChecker,
    SettingsController $settings,
    SubscribersFeature $subscribers,
    WPFunctions $wp
  ) {
    $this->servicesChecker = $servicesChecker;
    $this->subscribersFeature = $subscribers;
    $this->wp = $wp;
    $this->bridge = $bridge;
    $this->settings = $settings;
  }

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
          if ($result['message'] === self::MAILPOET_BRIDGE_DMRAC_ERROR) {
            $message .= $this->getDmarcMessage($result, $sender);
          } else {
            $message .= ' ' . $result['message'];
          }
          break;
        }
        try {
          $subscribersErrors = $this->getSubscribersErrors($resultParsed, $subscribers);
          $level = MailerError::LEVEL_SOFT;
        } catch (InvalidArgumentException $e) {
          $message .= ' ' . $e->getMessage();
        }
        break;
      case API::RESPONSE_CODE_INTERNAL_SERVER_ERROR:
      case API::RESPONSE_CODE_BAD_GATEWAY:
      case API::RESPONSE_CODE_TEMPORARY_UNAVAILABLE:
      case API::RESPONSE_CODE_GATEWAY_TIMEOUT:
        $message = __('Email service is temporarily not available, please try again in a few minutes.', 'mailpoet');
        $retryInterval = self::TEMPORARY_UNAVAILABLE_RETRY_INTERVAL;
        break;
      case API::RESPONSE_CODE_CAN_NOT_SEND:
        [$operation, $message] = $this->getCanNotSendError($result, $sender);
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
    $notice = new UnauthorizedEmailNotice($this->wp, null);
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

  private function getDmarcMessage($result, $sender): string {
    $messageToAppend = __('[link1]Click here to start the authentication[/link1].', 'mailpoet');
    $senderEmail = $sender['from_email'] ?? '';

    $appendMessage = Helpers::replaceLinkTags(
      $messageToAppend,
      '#',
      [
        'class' => 'mailpoet-js-button-authorize-email-and-sender-domain',
        'data-email' => $senderEmail,
        'data-type' => 'domain',
        'rel' => 'noopener noreferrer',
      ],
      'link1'
    );
    $final = ' ' . $result['message'] . ' ' . $appendMessage;
    return $final;
  }

  private function getEmailVolumeLimitReachedMessage(): string {
    $partialApiKey = $this->servicesChecker->generatePartialApiKey();
    $emailVolumeLimit = $this->subscribersFeature->getEmailVolumeLimit();
    $date = Carbon::now()->startOfMonth()->addMonth();
    $message = sprintf(
      // translators: %1$s is email volume limit and %2$s the date when you can resume sending.
      __('You have sent more emails this month than your MailPoet plan includes (%1$s), and sending has been temporarily paused. To continue sending with MailPoet Sending Service please [link]upgrade your plan[/link], or wait until sending is automatically resumed on <b>%2$s</b>.', 'mailpoet'),
      $emailVolumeLimit,
      $this->wp->dateI18n(get_option('date_format'), $date->getTimestamp())
    );
    $message = Helpers::replaceLinkTags(
      $message,
      "https://account.mailpoet.com/orders/upgrade/{$partialApiKey}",
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ]
    );

    return "{$message}<br/>";
  }

  private function getPendingApprovalMessage(): string {
    $message = __("Your subscription is currently [link]pending approval[/link]. You’ll soon be able to send once our team reviews your account. In the meantime, you can send previews to your authorized emails.", 'mailpoet');
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/350-pending-approval-subscription',
      [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
        'data-beacon-article' => '5fbd3942cff47e00160bd248',
      ]
    );

    return "{$message}<br/>";
  }

  /**
   * Returns error $message and $operation for API::RESPONSE_CODE_CAN_NOT_SEND
   */
  private function getCanNotSendError(array $result, $sender): array {
    if ($result['message'] === MailerError::MESSAGE_PENDING_APPROVAL) {
      $operation = MailerError::OPERATION_PENDING_APPROVAL;
      $message = $this->getPendingApprovalMessage();
      return [$operation, $message];
    }

    if ($result['message'] === MailerError::MESSAGE_EMAIL_INSUFFICIENT_PRIVILEGES) {
      $operation = MailerError::OPERATION_INSUFFICIENT_PRIVILEGES;
      $message = $this->getInsufficientPrivilegesMessage();
      return [$operation, $message];
    }

    if ($result['message'] === MailerError::MESSAGE_EMAIL_VOLUME_LIMIT_REACHED) {
      // Update the current email volume limit from MSS
      $premiumKey = $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
      $result = $this->bridge->checkPremiumKey($premiumKey);
      $this->bridge->storePremiumKeyAndState($premiumKey, $result);

      $operation = MailerError::OPERATION_EMAIL_LIMIT_REACHED;
      $message = $this->getEmailVolumeLimitReachedMessage();
      return [$operation, $message];
    }

    if ($result['message'] === MailerError::MESSAGE_EMAIL_NOT_AUTHORIZED) {
      $operation = MailerError::OPERATION_AUTHORIZATION;
      $message = $this->getUnauthorizedEmailMessage($sender);
      return [$operation, $message];
    }

    $message = $this->getAccountBannedMessage();
    $operation = MailerError::OPERATION_SEND;
    return [$operation, $message];
  }
}
