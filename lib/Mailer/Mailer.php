<?php

namespace MailPoet\Mailer;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Mailer\Methods\AmazonSES;
use MailPoet\Mailer\Methods\ErrorMappers\AmazonSESMapper;
use MailPoet\Mailer\Methods\ErrorMappers\MailPoetMapper;
use MailPoet\Mailer\Methods\ErrorMappers\PHPMailMapper;
use MailPoet\Mailer\Methods\ErrorMappers\SendGridMapper;
use MailPoet\Mailer\Methods\ErrorMappers\SMTPMapper;
use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Mailer\Methods\PHPMail;
use MailPoet\Mailer\Methods\SendGrid;
use MailPoet\Mailer\Methods\SMTP;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class Mailer {
  public $mailerConfig;
  public $sender;
  public $replyTo;
  public $returnPath;
  public $mailerInstance;
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  const MAILER_CONFIG_SETTING_NAME = 'mta';
  const SENDING_LIMIT_INTERVAL_MULTIPLIER = 60;
  const METHOD_MAILPOET = 'MailPoet';
  const METHOD_AMAZONSES = 'AmazonSES';
  const METHOD_SENDGRID = 'SendGrid';
  const METHOD_PHPMAIL = 'PHPMail';
  const METHOD_SMTP = 'SMTP';

  public function __construct(SettingsController $settings = null, WPFunctions $wp = null) {
    if (!$settings) {
      $settings = SettingsController::getInstance();
    }
    if (!$wp) {
      $wp = WPFunctions::get();
    }
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function init($mailer = false, $sender = false, $replyTo = false, $returnPath = false) {
    $this->mailerConfig = $this->getMailerConfig($mailer);
    $this->sender = $this->getSenderNameAndAddress($sender);
    $this->replyTo = $this->getReplyToNameAndAddress($replyTo);
    $this->returnPath = $this->getReturnPathAddress($returnPath);
    $this->mailerInstance = $this->buildMailer();
  }

  public function send($newsletter, $subscriber, $extraParams = []) {
    if (!$this->mailerInstance) {
      $this->init();
    }
    $subscriber = $this->formatSubscriberNameAndEmailAddress($subscriber);
    return $this->mailerInstance->send($newsletter, $subscriber, $extraParams);
  }

  private function buildMailer() {
    switch ($this->mailerConfig['method']) {
      case self::METHOD_AMAZONSES:
        $mailerInstance = new AmazonSES(
          $this->mailerConfig['region'],
          $this->mailerConfig['access_key'],
          $this->mailerConfig['secret_key'],
          $this->sender,
          $this->replyTo,
          $this->returnPath,
          new AmazonSESMapper()
        );
        break;
      case self::METHOD_MAILPOET:
        $mailerInstance = new MailPoet(
          $this->mailerConfig['mailpoet_api_key'],
          $this->sender,
          $this->replyTo,
          new MailPoetMapper(),
          ContainerWrapper::getInstance()->get(AuthorizedEmailsController::class)
        );
        break;
      case self::METHOD_SENDGRID:
        $mailerInstance = new SendGrid(
          $this->mailerConfig['api_key'],
          $this->sender,
          $this->replyTo,
          new SendGridMapper()
        );
        break;
      case self::METHOD_PHPMAIL:
        $mailerInstance = new PHPMail(
          $this->sender,
          $this->replyTo,
          $this->returnPath,
          new PHPMailMapper()
        );
        break;
      case self::METHOD_SMTP:
        $mailerInstance = new SMTP(
          $this->mailerConfig['host'],
          $this->mailerConfig['port'],
          $this->mailerConfig['authentication'],
          $this->mailerConfig['encryption'],
          $this->sender,
          $this->replyTo,
          $this->returnPath,
          new SMTPMapper(),
          $this->mailerConfig['login'],
          $this->mailerConfig['password']
        );
        break;
      default:
        throw new \Exception(__('Mailing method does not exist.', 'mailpoet'));
    }
    return $mailerInstance;
  }

  private function getMailerConfig($mailer = false) {
    if (!$mailer) {
      $mailer = $this->settings->get(self::MAILER_CONFIG_SETTING_NAME);
      if (!$mailer || !isset($mailer['method'])) throw new \Exception(__('Mailer is not configured.', 'mailpoet'));
    }
    return $mailer;
  }

  private function getSenderNameAndAddress($sender = false) {
    if (empty($sender)) {
      $sender = $this->settings->get('sender', []);
      if (empty($sender['address'])) throw new \Exception(__('Sender name and email are not configured.', 'mailpoet'));
    }
    $fromName = $this->encodeAddressNamePart($sender['name']);
    return [
      'from_name' => $fromName,
      'from_email' => $sender['address'],
      'from_name_email' => sprintf('%s <%s>', $fromName, $sender['address']),
    ];
  }

  public function getReplyToNameAndAddress($replyTo = []) {
    if (!$replyTo) {
      $replyTo = $this->settings->get('reply_to');
      $replyTo['name'] = (!empty($replyTo['name'])) ?
        $replyTo['name'] :
        $this->sender['from_name'];
      $replyTo['address'] = (!empty($replyTo['address'])) ?
        $replyTo['address'] :
        $this->sender['from_email'];
    }
    if (empty($replyTo['address'])) {
      $replyTo['address'] = $this->sender['from_email'];
    }
    $replyToName = $this->encodeAddressNamePart($replyTo['name']);
    return [
      'reply_to_name' => $replyToName,
      'reply_to_email' => $replyTo['address'],
      'reply_to_name_email' => sprintf('%s <%s>', $replyToName, $replyTo['address']),
    ];
  }

  public function getReturnPathAddress($returnPath) {
    if ($returnPath) {
      return $returnPath;
    }
    $bounceAddress = $this->settings->get('bounce.address');
    return $this->wp->isEmail($bounceAddress) ? $bounceAddress : null;
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
