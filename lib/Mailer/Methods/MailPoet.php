<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;

class MailPoet {
  public $api;
  public $sender;
  public $replyTo;
  public $servicesChecker;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var MailPoetMapper */
  private $errorMapper;

  /** @var BlacklistCheck */
  private $blacklist;

  public function __construct($apiKey, $sender, $replyTo, MailPoetMapper $errorMapper, AuthorizedEmailsController $authorizedEmailsController) {
    $this->api = new API($apiKey);
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->servicesChecker = new ServicesChecker();
    $this->errorMapper = $errorMapper;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->blacklist = new BlacklistCheck();
  }

  public function send($newsletter, $subscriber, $extraParams = []) {
    if ($this->servicesChecker->isMailPoetAPIKeyValid() === false) {
      return Mailer::formatMailerErrorResult($this->errorMapper->getInvalidApiKeyError());
    }

    $subscribersForBlacklistCheck = is_array($subscriber) ? $subscriber : [$subscriber];
    foreach ($subscribersForBlacklistCheck as $sub) {
      if ($this->blacklist->isBlacklisted($sub)) {
        $error = $this->errorMapper->getBlacklistError($sub);
        return Mailer::formatMailerErrorResult($error);
      }
    }

    $messageBody = $this->getBody($newsletter, $subscriber, $extraParams);
    $result = $this->api->sendMessages($messageBody);

    switch ($result['status']) {
      case API::SENDING_STATUS_CONNECTION_ERROR:
        $error = $this->errorMapper->getConnectionError($result['message']);
        return Mailer::formatMailerErrorResult($error);
      case API::SENDING_STATUS_SEND_ERROR:
        $error = $this->processSendError($result, $subscriber, $newsletter);
        return Mailer::formatMailerErrorResult($error);
      case API::SENDING_STATUS_OK:
      default:
        return Mailer::formatMailerSendSuccessResult();
    }
  }

  public function processSendError($result, $subscriber, $newsletter) {
    if (!empty($result['code']) && $result['code'] === API::RESPONSE_CODE_KEY_INVALID) {
      Bridge::invalidateKey();
    } elseif (!empty($result['code'])
      && $result['code'] === API::RESPONSE_CODE_CAN_NOT_SEND
      && $result['message'] === MailerError::MESSAGE_EMAIL_NOT_AUTHORIZED
    ) {
      $this->authorizedEmailsController->checkAuthorizedEmailAddresses();
    }
    return $this->errorMapper->getErrorForResult($result, $subscriber, $this->sender, $newsletter);
  }

  public function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriberData);
    if (!isset($subscriberData['email'])) {
      $subscriberData = [
        'email' => $subscriber,
      ];
    }
    return [
      'email' => $subscriberData['email'],
      'name' => (isset($subscriberData['name'])) ? $subscriberData['name'] : '',
    ];
  }

  public function getBody($newsletter, $subscriber, $extraParams = []) {
    if (is_array($newsletter) && is_array($subscriber)) {
      $body = [];
      for ($record = 0; $record < count($newsletter); $record++) {
        $body[] = $this->composeBody(
          $newsletter[$record],
          $this->processSubscriber($subscriber[$record]),
          (!empty($extraParams['unsubscribe_url'][$record])) ? $extraParams['unsubscribe_url'][$record] : false,
          (!empty($extraParams['meta'][$record])) ? $extraParams['meta'][$record] : false
        );
      }
    } else {
      $body[] = $this->composeBody(
        $newsletter,
        $this->processSubscriber($subscriber),
        (!empty($extraParams['unsubscribe_url'])) ? $extraParams['unsubscribe_url'] : false,
        (!empty($extraParams['meta'])) ? $extraParams['meta'] : false
      );
    }
    return $body;
  }

  private function composeBody($newsletter, $subscriber, $unsubscribeUrl, $meta) {
    $body = [
      'to' => ([
        'address' => $subscriber['email'],
        'name' => $subscriber['name'],
      ]),
      'from' => ([
        'address' => $this->sender['from_email'],
        'name' => $this->sender['from_name'],
      ]),
      'reply_to' => ([
        'address' => $this->replyTo['reply_to_email'],
        'name' => $this->replyTo['reply_to_name'],
      ]),
      'subject' => $newsletter['subject'],
    ];
    if (!empty($newsletter['body']['html'])) {
      $body['html'] = $newsletter['body']['html'];
    }
    if (!empty($newsletter['body']['text'])) {
      $body['text'] = $newsletter['body']['text'];
    }
    if ($unsubscribeUrl) {
      $body['list_unsubscribe'] = $unsubscribeUrl;
    }
    if ($meta) {
      $body['meta'] = $meta;
    }
    return $body;
  }
}
