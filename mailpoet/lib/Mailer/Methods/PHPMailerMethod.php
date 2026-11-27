<?php declare(strict_types = 1);

namespace MailPoet\Mailer\Methods;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\Methods\Common\BlacklistCheck;
use MailPoet\Mailer\Methods\Common\ExtraRecipients;
use MailPoet\Mailer\WordPress\PHPMailerLoader;
use MailPoet\Util\Url;
use PHPMailer\PHPMailer\PHPMailer;

PHPMailerLoader::load();

abstract class PHPMailerMethod implements MailerMethod {
  /** @var string[] */
  public $sender;
  /** @var string[] */
  public $replyTo;
  /** @var string */
  public $returnPath;
  /** @var PHPMailer  */
  public $mailer;

  protected $errorMapper;

  /** @var Url */
  protected $urlUtils;

  /** @var BlacklistCheck */
  protected $blacklist;

  public function __construct(
    $sender,
    $replyTo,
    $returnPath,
    $errorMapper,
    Url $urlUtils
  ) {
    $this->sender = $sender;
    $this->replyTo = $replyTo;
    $this->returnPath = $returnPath;
    $this->mailer = $this->buildMailer();
    $this->errorMapper = $errorMapper;
    $this->urlUtils = $urlUtils;
    $this->blacklist = new BlacklistCheck();
  }

  public function send($newsletter, $subscriber, $extraParams = []): array {
    if ($this->blacklist->isBlacklisted($subscriber)) {
      $error = $this->errorMapper->getBlacklistError($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
    $blacklistedRecipient = ExtraRecipients::findBlacklisted($this->blacklist, $extraParams);
    if ($blacklistedRecipient !== null) {
      return Mailer::formatMailerErrorResult($this->errorMapper->getBlacklistError($blacklistedRecipient));
    }
    $acceptedCount = 0;
    $rejectedCount = 0;
    $originalCallback = $this->mailer->action_function; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    try {
      $mailer = $this->configureMailerWithMessage($newsletter, $subscriber, $extraParams);
      // A site can set its own delivery callback through the mailpoet_mailer_smtp_instance filter.
      $mailer->action_function = function ($isSent, ...$args) use (&$acceptedCount, &$rejectedCount, $originalCallback): void { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($isSent) {
          $acceptedCount++;
        } else {
          $rejectedCount++;
        }
        if (is_callable($originalCallback)) {
          call_user_func($originalCallback, $isSent, ...$args);
        }
      };
      $result = $mailer->send();
    } catch (\Exception $e) {
      // SMTP delivers to the accepted recipients before PHPMailer throws for the rejected ones.
      // Reporting an error here would make the WordPress mailer fallback send them a second copy.
      if ($acceptedCount > 0) {
        LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_SENDING)->warning(
          'Some recipients of an email were rejected by the sending server',
          ['accepted' => $acceptedCount, 'rejected' => $rejectedCount]
        );
        return Mailer::formatMailerSendSuccessResult();
      }
      return Mailer::formatMailerErrorResult($this->errorMapper->getErrorFromException($e, $subscriber));
    } finally {
      $this->mailer->action_function = $originalCallback; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    if ($result === true) {
      return Mailer::formatMailerSendSuccessResult();
    } else {
      $error = $this->errorMapper->getErrorForSubscriber($subscriber);
      return Mailer::formatMailerErrorResult($error);
    }
  }

  abstract public function buildMailer(): PHPMailer;

  public function configureMailerWithMessage($newsletter, $subscriber, $extraParams = []) {
    $mailer = $this->mailer;
    $mailer->clearAllRecipients();
    $mailer->clearCustomHeaders();
    $mailer->isHTML(!empty($newsletter['body']['html']));
    $mailer->CharSet = 'UTF-8'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $mailer->setFrom($this->sender['from_email'], $this->sender['from_name'], false);
    $mailer->addReplyTo($this->replyTo['reply_to_email'], $this->replyTo['reply_to_name']);
    $subscriber = $this->processSubscriber($subscriber);
    $mailer->addAddress($subscriber['email'], $subscriber['name']);
    $extraRecipients = ExtraRecipients::fromExtraParams($extraParams);
    foreach ($extraRecipients['to'] as $recipient) {
      $mailer->addAddress($recipient['address'], $recipient['name']);
    }
    foreach ($extraRecipients['cc'] as $recipient) {
      $mailer->addCC($recipient['address'], $recipient['name']);
    }
    foreach ($extraRecipients['bcc'] as $recipient) {
      $mailer->addBCC($recipient['address'], $recipient['name']);
    }
    $mailer->Subject = (!empty($newsletter['subject'])) ? $newsletter['subject'] : ''; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $mailer->Body = (!empty($newsletter['body']['html'])) ? $newsletter['body']['html'] : $newsletter['body']['text']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if ($mailer->ContentType !== 'text/plain') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $mailer->AltBody = (!empty($newsletter['body']['text'])) ? $newsletter['body']['text'] : ''; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    $mailer->Sender = $this->returnPath; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    // unsubscribe header
    $unsubscribeUrl = $extraParams['unsubscribe_url'] ?? null;
    $oneClickUnsubscribeUrl = $extraParams['one_click_unsubscribe'] ?? null;
    if ($unsubscribeUrl) {
      $isHttps = $this->urlUtils->isUsingHttps($unsubscribeUrl);
      $url = $isHttps && $oneClickUnsubscribeUrl ? $oneClickUnsubscribeUrl : $unsubscribeUrl;
      if ($isHttps) {
        $mailer->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
      }
      $mailer->addCustomHeader('List-Unsubscribe', '<' . $url . '>');
    }

    // Enforce base64 encoding when lines are too long, otherwise quoted-printable encoding
    // is automatically used which can occasionally break the email body.
    // Explanation:
    //   The bug occurs on Unix systems where mail() function passes email to a variation of
    //   sendmail command which expects only NL as line endings (POSIX). Since quoted-printable
    //   requires CRLF some of those commands convert LF to CRLF which can break the email body
    //   because it already (correctly) uses CRLF. Such CRLF then (wrongly) becomes CRCRLF.
    if (PHPMailer::hasLineLongerThanMax($mailer->Body)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $mailer->Encoding = 'base64'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    return $mailer;
  }

  public function processSubscriber($subscriber) {
    // Matches "Name <email>"
    preg_match('!^(?P<name>.*)\s<(?P<email>[^<>]+)>\z!s', $subscriber, $subscriberData);
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
