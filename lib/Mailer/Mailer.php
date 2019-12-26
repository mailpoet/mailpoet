<?php

namespace MailPoet\Mailer;

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
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class Mailer {
  public $mailer_config;
  public $sender;
  public $reply_to;
  public $return_path;
  public $mailer_instance;
  /** @var SettingsController */
  private $settings;
  const MAILER_CONFIG_SETTING_NAME = 'mta';
  const SENDING_LIMIT_INTERVAL_MULTIPLIER = 60;
  const METHOD_MAILPOET = 'MailPoet';
  const METHOD_AMAZONSES = 'AmazonSES';
  const METHOD_SENDGRID = 'SendGrid';
  const METHOD_PHPMAIL = 'PHPMail';
  const METHOD_SMTP = 'SMTP';

  public function __construct(SettingsController $settings = null) {
    if (!$settings) {
      $settings = SettingsController::getInstance();
    }
    $this->settings = $settings;
  }

  public function init($mailer = false, $sender = false, $reply_to = false, $return_path = false) {
    $this->mailer_config = $this->getMailerConfig($mailer);
    $this->sender = $this->getSenderNameAndAddress($sender);
    $this->reply_to = $this->getReplyToNameAndAddress($reply_to);
    $this->return_path = $this->getReturnPathAddress($return_path);
    $this->mailer_instance = $this->buildMailer();
  }

  public function send($newsletter, $subscriber, $extra_params = []) {
    if (!$this->mailer_instance) {
      $this->init();
    }
    $subscriber = $this->formatSubscriberNameAndEmailAddress($subscriber);
    return $this->mailer_instance->send($newsletter, $subscriber, $extra_params);
  }

  private function buildMailer() {
    switch ($this->mailer_config['method']) {
      case self::METHOD_AMAZONSES:
        $mailer_instance = new AmazonSES(
          $this->mailer_config['region'],
          $this->mailer_config['access_key'],
          $this->mailer_config['secret_key'],
          $this->sender,
          $this->reply_to,
          $this->return_path,
          new AmazonSESMapper()
        );
        break;
      case self::METHOD_MAILPOET:
        $mailer_instance = new MailPoet(
          $this->mailer_config['mailpoet_api_key'],
          $this->sender,
          $this->reply_to,
          new MailPoetMapper(),
          new AuthorizedEmailsController($this->settings, new Bridge)
        );
        break;
      case self::METHOD_SENDGRID:
        $mailer_instance = new SendGrid(
          $this->mailer_config['api_key'],
          $this->sender,
          $this->reply_to,
          new SendGridMapper()
        );
        break;
      case self::METHOD_PHPMAIL:
        $mailer_instance = new PHPMail(
          $this->sender,
          $this->reply_to,
          $this->return_path,
          new PHPMailMapper()
        );
        break;
      case self::METHOD_SMTP:
        $mailer_instance = new SMTP(
          $this->mailer_config['host'],
          $this->mailer_config['port'],
          $this->mailer_config['authentication'],
          $this->mailer_config['login'],
          $this->mailer_config['password'],
          $this->mailer_config['encryption'],
          $this->sender,
          $this->reply_to,
          $this->return_path,
          new SMTPMapper()
        );
        break;
      default:
        throw new \Exception(__('Mailing method does not exist.', 'mailpoet'));
    }
    return $mailer_instance;
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
    $from_name = $this->encodeAddressNamePart($sender['name']);
    return [
      'from_name' => $from_name,
      'from_email' => $sender['address'],
      'from_name_email' => sprintf('%s <%s>', $from_name, $sender['address']),
    ];
  }

  public function getReplyToNameAndAddress($reply_to = []) {
    if (!$reply_to) {
      $reply_to = $this->settings->get('reply_to');
      $reply_to['name'] = (!empty($reply_to['name'])) ?
        $reply_to['name'] :
        $this->sender['from_name'];
      $reply_to['address'] = (!empty($reply_to['address'])) ?
        $reply_to['address'] :
        $this->sender['from_email'];
    }
    if (empty($reply_to['address'])) {
      $reply_to['address'] = $this->sender['from_email'];
    }
    $reply_to_name = $this->encodeAddressNamePart($reply_to['name']);
    return [
      'reply_to_name' => $reply_to_name,
      'reply_to_email' => $reply_to['address'],
      'reply_to_name_email' => sprintf('%s <%s>', $reply_to_name, $reply_to['address']),
    ];
  }

  public function getReturnPathAddress($return_path) {
    return ($return_path) ?
      $return_path :
      $this->settings->get('bounce.address');
  }

  /**
   * @param  \MailPoet\Models\Subscriber|array $subscriber
   */
  public function formatSubscriberNameAndEmailAddress($subscriber) {
    $subscriber = (is_object($subscriber)) ? $subscriber->asArray() : $subscriber;
    if (!is_array($subscriber)) return $subscriber;
    if (isset($subscriber['address'])) $subscriber['email'] = $subscriber['address'];
    $first_name = (isset($subscriber['first_name'])) ? $subscriber['first_name'] : '';
    $last_name = (isset($subscriber['last_name'])) ? $subscriber['last_name'] : '';
    $full_name = (isset($subscriber['full_name'])) ? $subscriber['full_name'] : null;
    if (!$first_name && !$last_name && !$full_name) return $subscriber['email'];
    $full_name = is_null($full_name) ? sprintf('%s %s', $first_name, $last_name) : $full_name;
    $full_name = trim(preg_replace('!\s\s+!', ' ', $full_name));
    $full_name = $this->encodeAddressNamePart($full_name);
    $subscriber = sprintf(
      '%s <%s>',
      $full_name,
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
