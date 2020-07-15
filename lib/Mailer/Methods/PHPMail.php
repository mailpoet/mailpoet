<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\Mailer\WordPress\PHPMailerLoader;

PHPMailerLoader::load();

class PHPMail {
  public $sender;
  public $replyTo;
  public $returnPath;
  public $mailer;

  /** @var PHPMailMapper  */
  private $errorMapper;

  /** @var BlacklistCheck */
  private $blacklist;

  public function __construct($sender, $replyTo, $returnPath, PHPMailMapper $errorMapper) {
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->returnPath = ($returnPath) ?
      $returnPath :
      $this->sender['from_email'];
    $this->mailer = $this->buildMailer();
    $this->errorMapper = $errorMapper;
    $this->blacklist = new BlacklistCheck();
  }

  public function send($newsletter, $subscriber, $extraParams = []) {
    if ($this->blacklist->isBlacklisted($subscriber)) {
      $error = $this->errorMapper->getBlacklistError($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    try {
      $mailer = $this->configureMailerWithMessage($newsletter, $subscriber, $extraParams);
      $result = $mailer->send();
    } catch (\Exception $e) {
      return Mailer::formatMailerErrorResult($this->errorMapper->getErrorFromException($e, $subscriber));
    }
    if ($result === true) {
      return Mailer::formatMailerSendSuccessResult();
    } else {
      $error = $this->errorMapper->getErrorForSubscriber($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
  }

  public function buildMailer() {
    $mailer = new \PHPMailer(true);
    // send using PHP's mail() function
    $mailer->isMail();
    return $mailer;
  }

  public function configureMailerWithMessage($newsletter, $subscriber, $extraParams = []) {
    $mailer = $this->mailer;
    $mailer->clearAddresses();
    $mailer->clearCustomHeaders();
    $mailer->isHTML(!empty($newsletter['body']['html']));
    $mailer->CharSet = 'UTF-8'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $mailer->setFrom($this->sender['from_email'], $this->sender['from_name'], false);
    $mailer->addReplyTo($this->replyTo['reply_to_email'], $this->replyTo['reply_to_name']);
    $subscriber = $this->processSubscriber($subscriber);
    $mailer->addAddress($subscriber['email'], $subscriber['name']);
    $mailer->Subject = (!empty($newsletter['subject'])) ? $newsletter['subject'] : ''; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $mailer->Body = (!empty($newsletter['body']['html'])) ? $newsletter['body']['html'] : $newsletter['body']['text']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if ($mailer->ContentType !== 'text/plain') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      $mailer->AltBody = (!empty($newsletter['body']['text'])) ? $newsletter['body']['text'] : ''; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    }
    $mailer->Sender = $this->returnPath; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    if (!empty($extraParams['unsubscribe_url'])) {
      $this->mailer->addCustomHeader('List-Unsubscribe', $extraParams['unsubscribe_url']);
    }

    // Enforce base64 encoding when lines are too long, otherwise quoted-printable encoding
    // is automatically used which can occasionally break the email body.
    // Explanation:
    //   The bug occurs on Unix systems where mail() function passes email to a variation of
    //   sendmail command which expects only NL as line endings (POSIX). Since quoted-printable
    //   requires CRLF some of those commands convert LF to CRLF which can break the email body
    //   because it already (correctly) uses CRLF. Such CRLF then (wrongly) becomes CRCRLF.
    if (\PHPMailer::hasLineLongerThanMax($mailer->Body)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      $mailer->Encoding = 'base64'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    }

    return $mailer;
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
}
