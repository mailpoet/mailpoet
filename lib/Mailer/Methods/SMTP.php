<?php

namespace MailPoet\Mailer\Methods;

use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\ErrorMappers\SMTPMapper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Swift_Mailer;
use MailPoetVendor\Swift_Message;
use MailPoetVendor\Swift_Plugins_LoggerPlugin;
use MailPoetVendor\Swift_Plugins_Loggers_ArrayLogger;
use MailPoetVendor\Swift_SmtpTransport;

class SMTP {
  public $host;
  public $port;
  public $authentication;
  public $login;
  public $password;
  public $encryption;
  public $sender;
  public $replyTo;
  public $returnPath;
  public $mailer;
  private $mailerLogger;
  const SMTP_CONNECTION_TIMEOUT = 15; // seconds

  /** @var SMTPMapper */
  private $errorMapper;

  /** @var BlacklistCheck */
  private $blacklist;

  private $wp;

  public function __construct(
    $host,
    $port,
    $authentication,
    $encryption,
    $sender,
    $replyTo,
    $returnPath,
    SMTPMapper $errorMapper,
    $login = null,
    $password = null
  ) {
    $this->wp = new WPFunctions;
    $this->host = $host;
    $this->port = $port;
    $this->authentication = $authentication;
    $this->login = $login;
    $this->password = $password;
    $this->encryption = $encryption;
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->returnPath = ($returnPath) ?
      $returnPath :
      $this->sender['from_email'];
    $this->mailer = $this->buildMailer();
    $this->mailerLogger = new Swift_Plugins_Loggers_ArrayLogger();
    $this->mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($this->mailerLogger));
    $this->errorMapper = $errorMapper;
    $this->blacklist = new BlacklistCheck();
  }

  public function send($newsletter, $subscriber, $extraParams = []) {
    if ($this->blacklist->isBlacklisted($subscriber)) {
      $error = $this->errorMapper->getBlacklistError($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    try {
      $message = $this->createMessage($newsletter, $subscriber, $extraParams);
      $result = $this->mailer->send($message);
    } catch (\Exception $e) {
      return Mailer::formatMailerErrorResult(
        $this->errorMapper->getErrorFromException($e, $subscriber)
      );
    }
    if ($result === 1) {
      return Mailer::formatMailerSendSuccessResult();
    } else {
      $error = $this->errorMapper->getErrorFromLog($this->mailerLogger->dump(), $subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
  }

  public function buildMailer() {
    $transport = new Swift_SmtpTransport($this->host, $this->port, $this->encryption);
    $connectionTimeout = $this->wp->applyFilters('mailpoet_mailer_smtp_connection_timeout', self::SMTP_CONNECTION_TIMEOUT);
    $transport->setTimeout($connectionTimeout);
    if ($this->authentication) {
      $transport
        ->setUsername($this->login)
        ->setPassword($this->password);
    }
    $transport = $this->wp->applyFilters('mailpoet_mailer_smtp_transport_agent', $transport);
    return new Swift_Mailer($transport);
  }

  public function createMessage($newsletter, $subscriber, $extraParams = []) {
    $message = (new Swift_Message())
      ->setTo($this->processSubscriber($subscriber))
      ->setFrom(
        [
          $this->sender['from_email'] => $this->sender['from_name'],
        ]
      )
      ->setSender($this->sender['from_email'])
      ->setReplyTo(
        [
          $this->replyTo['reply_to_email'] => $this->replyTo['reply_to_name'],
        ]
      )
      ->setReturnPath($this->returnPath)
      ->setSubject($newsletter['subject']);
    if (!empty($extraParams['unsubscribe_url'])) {
      $headers = $message->getHeaders();
      $headers->addTextHeader('List-Unsubscribe', '<' . $extraParams['unsubscribe_url'] . '>');
    }
    if (!empty($newsletter['body']['html'])) {
      $message = $message->setBody($newsletter['body']['html'], 'text/html');
    }
    if (!empty($newsletter['body']['text'])) {
      $message = $message->addPart($newsletter['body']['text'], 'text/plain');
    }
    return $message;
  }

  public function processSubscriber($subscriber) {
    preg_match('!(?P<name>.*?)\s<(?P<email>.*?)>!', $subscriber, $subscriberData);
    if (!isset($subscriberData['email'])) {
      $subscriberData = [
        'email' => $subscriber,
      ];
    }
    return [
      $subscriberData['email'] =>
        (isset($subscriberData['name'])) ? $subscriberData['name'] : '',
    ];
  }
}
