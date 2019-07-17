<?php
namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;

if (!defined('ABSPATH')) exit;

class MailPoet {
  public $api;
  public $sender;
  public $reply_to;
  public $services_checker;

  /** @var AuthorizedEmailsController */
  private $authorized_emails_controller;

  /** @var MailPoetMapper */
  private $error_mapper;

  /** @var BlacklistCheck */
  private $blacklist;

  function __construct($api_key, $sender, $reply_to, MailPoetMapper $error_mapper, AuthorizedEmailsController $authorized_emails_controller) {
    $this->api = new API($api_key);
    $this->sender = $sender;
    $this->reply_to = $reply_to;
    $this->services_checker = new ServicesChecker();
    $this->error_mapper = $error_mapper;
    $this->authorized_emails_controller = $authorized_emails_controller;
    $this->blacklist = new BlacklistCheck();
  }

  function send($newsletter, $subscriber, $extra_params = []) {
    if ($this->services_checker->isMailPoetAPIKeyValid() === false) {
      return Mailer::formatMailerErrorResult($this->error_mapper->getInvalidApiKeyError());
    }

    $subscribers_for_blacklist_check = is_array($subscriber) ? $subscriber : [$subscriber];
    foreach ($subscribers_for_blacklist_check as $sub) {
      if ($this->blacklist->isBlacklisted($sub)) {
        $error = $this->error_mapper->getBlacklistError($sub);
        return Mailer::formatMailerErrorResult($error);
      }
    }

    $message_body = $this->getBody($newsletter, $subscriber, $extra_params);
    $result = $this->api->sendMessages($message_body);

    switch ($result['status']) {
      case API::SENDING_STATUS_CONNECTION_ERROR:
        $error = $this->error_mapper->getConnectionError($result['message']);
        return Mailer::formatMailerErrorResult($error);
      case API::SENDING_STATUS_SEND_ERROR:
        $error = $this->processSendError($result, $subscriber, $newsletter);
        return Mailer::formatMailerErrorResult($error);
      case API::SENDING_STATUS_OK:
      default:
        return Mailer::formatMailerSendSuccessResult();
    }
  }

  function processSendError($result, $subscriber, $newsletter) {
    if (!empty($result['code']) && $result['code'] === API::RESPONSE_CODE_KEY_INVALID) {
      Bridge::invalidateKey();
    } elseif (!empty($result['code'])
      && $result['code'] === API::RESPONSE_CODE_CAN_NOT_SEND
      && $result['message'] === MailerError::MESSAGE_EMAIL_NOT_AUTHORIZED
    ) {
      $this->authorized_emails_controller->checkAuthorizedEmailAddresses();
    }
    return $this->error_mapper->getErrorForResult($result, $subscriber, $this->sender, $newsletter);
  }

  function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriber_data);
    if (!isset($subscriber_data['email'])) {
      $subscriber_data = [
        'email' => $subscriber,
      ];
    }
    return [
      'email' => $subscriber_data['email'],
      'name' => (isset($subscriber_data['name'])) ? $subscriber_data['name'] : '',
    ];
  }

  function getBody($newsletter, $subscriber, $extra_params = []) {
    $_this = $this;
    $composeBody = function($newsletter, $subscriber, $unsubscribe_url) use($_this) {
      $body = [
        'to' => ([
          'address' => $subscriber['email'],
          'name' => $subscriber['name'],
        ]),
        'from' => ([
          'address' => $_this->sender['from_email'],
          'name' => $_this->sender['from_name'],
        ]),
        'reply_to' => ([
          'address' => $_this->reply_to['reply_to_email'],
          'name' => $_this->reply_to['reply_to_name'],
        ]),
        'subject' => $newsletter['subject'],
      ];
      if (!empty($newsletter['body']['html'])) {
        $body['html'] = $newsletter['body']['html'];
      }
      if (!empty($newsletter['body']['text'])) {
        $body['text'] = $newsletter['body']['text'];
      }
      if ($unsubscribe_url) {
        $body['list_unsubscribe'] = $unsubscribe_url;
      }
      return $body;
    };
    if (is_array($newsletter) && is_array($subscriber)) {
      $body = [];
      for ($record = 0; $record < count($newsletter); $record++) {
        $body[] = $composeBody(
          $newsletter[$record],
          $this->processSubscriber($subscriber[$record]),
          (!empty($extra_params['unsubscribe_url'][$record])) ? $extra_params['unsubscribe_url'][$record] : false
        );
      }
    } else {
      $body[] = $composeBody(
        $newsletter,
        $this->processSubscriber($subscriber),
        (!empty($extra_params['unsubscribe_url'])) ? $extra_params['unsubscribe_url'] : false
      );
    }
    return $body;
  }
}
