<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Mailer;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Methods\MailerMethod;
use MailPoet\Models\Subscriber;

class Mailer {
  /** @var MailerMethod */
  public $mailerMethod;

  const MAILER_CONFIG_SETTING_NAME = 'mta';
  const SENDING_LIMIT_INTERVAL_MULTIPLIER = 60;
  const METHOD_MAILPOET = 'MailPoet';
  const METHOD_AMAZONSES = 'AmazonSES';
  const METHOD_SENDGRID = 'SendGrid';
  const METHOD_PHPMAIL = 'PHPMail';
  const METHOD_SMTP = 'SMTP';

  public function __construct(
    MailerMethod $mailerMethod
  ) {
    $this->mailerMethod = $mailerMethod;
  }

  public function send($newsletter, $subscriber, $extraParams = []) {
    // This if adds support for code that calls this method to use SubscriberEntity while the Mailer class is still using the old model.
    // Once we add support for SubscriberEntity in the Mailer class, this if can be removed.
    if ($subscriber instanceof SubscriberEntity) {
      $subscriber = Subscriber::findOne($subscriber->getId());
    }
    $subscriber = $this->formatSubscriberNameAndEmailAddress($subscriber);
    return $this->mailerMethod->send($newsletter, $subscriber, $extraParams);
  }

  /**
   * @param  \MailPoet\Models\Subscriber|array|string $subscriber
   */
  public function formatSubscriberNameAndEmailAddress($subscriber) {
    $subscriber = (is_object($subscriber)) ? $subscriber->asArray() : $subscriber;
    if (!is_array($subscriber)) return $subscriber;
    if (isset($subscriber['address'])) $subscriber['email'] = $subscriber['address'];
    $firstName = (isset($subscriber['first_name'])) ? $subscriber['first_name'] : '';
    $lastName = (isset($subscriber['last_name'])) ? $subscriber['last_name'] : '';
    $fullName = (isset($subscriber['full_name'])) ? $subscriber['full_name'] : null;
    if (!$firstName && !$lastName && !$fullName) return $subscriber['email'];
    $fullName = is_null($fullName) ? sprintf('%s %s', $firstName, $lastName) : $fullName;
    $fullName = trim(preg_replace('!\s\s+!', ' ', $fullName));
    $fullName = $this->encodeAddressNamePart($fullName);
    $subscriber = sprintf(
      '%s <%s>',
      $fullName,
      $subscriber['email']
    );
    return $subscriber;
  }

  public function encodeAddressNamePart($name) {
    if (mb_detect_encoding($name) === 'ASCII') return $name;
    // encode non-ASCII string as per RFC 2047 (https://www.ietf.org/rfc/rfc2047.txt)
    return sprintf('=?utf-8?B?%s?=', base64_encode($name));
  }

  public static function formatMailerErrorResult(MailerError $error) {
    return [
      'response' => false,
      'error' => $error,
    ];
  }

  public static function formatMailerSendSuccessResult() {
    return [
      'response' => true,
    ];
  }
}
