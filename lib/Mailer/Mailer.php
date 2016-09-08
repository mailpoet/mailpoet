<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Mailer {
  public $mailer_config;
  public $sender;
  public $reply_to;
  public $mailer_instance;
  const MAILER_CONFIG_SETTING_NAME = 'mta';
  const SENDING_LIMIT_INTERVAL_MULTIPLIER = 60;
  const METHOD_MAILPOET = 'MailPoet';
  const METHOD_MAILGUN = 'MailGun';
  const METHOD_ELASTICEMAIL = 'ElasticEmail';
  const METHOD_AMAZONSES = 'AmazonSES';
  const METHOD_SENDGRID = 'SendGrid';
  const METHOD_PHPMAIL = 'PHPMail';
  const METHOD_SMTP = 'SMTP';

  function __construct($mailer = false, $sender = false, $reply_to = false) {
    $this->mailer_config = self::getMailerConfig($mailer);
    $this->sender = $this->getSender($sender);
    $this->reply_to = $this->getReplyTo($reply_to);
    $this->mailer_instance = $this->buildMailer();
  }

  function send($newsletter, $subscriber) {
    $subscriber = $this->transformSubscriber($subscriber);
    return $this->mailer_instance->send($newsletter, $subscriber);
  }

  function buildMailer() {
    switch($this->mailer_config['method']) {
      case self::METHOD_AMAZONSES:
        $mailer_instance = new $this->mailer_config['class'](
          $this->mailer_config['region'],
          $this->mailer_config['access_key'],
          $this->mailer_config['secret_key'],
          $this->sender,
          $this->reply_to
        );
        break;
      case self::METHOD_ELASTICEMAIL:
        $mailer_instance = new $this->mailer_config['class'](
          $this->mailer_config['api_key'],
          $this->sender,
          $this->reply_to
        );
        break;
      case self::METHOD_MAILGUN:
        $mailer_instance = new $this->mailer_config['class'](
          $this->mailer_config['domain'],
          $this->mailer_config['api_key'],
          $this->sender,
          $this->reply_to
        );
        break;
      case self::METHOD_MAILPOET:
        $mailer_instance = new $this->mailer_config['class'](
          $this->mailer_config['mailpoet_api_key'],
          $this->sender,
          $this->reply_to
        );
        break;
      case self::METHOD_SENDGRID:
        $mailer_instance = new $this->mailer_config['class'](
          $this->mailer_config['api_key'],
          $this->sender,
          $this->reply_to
        );
        break;
      case self::METHOD_PHPMAIL:
        $mailer_instance = new $this->mailer_config['class'](
          $this->sender,
          $this->reply_to
        );
        break;
      case self::METHOD_SMTP:
        $mailer_instance = new $this->mailer_config['class'](
          $this->mailer_config['host'],
          $this->mailer_config['port'],
          $this->mailer_config['authentication'],
          $this->mailer_config['login'],
          $this->mailer_config['password'],
          $this->mailer_config['encryption'],
          $this->sender,
          $this->reply_to
        );
        break;
      default:
        throw new \Exception(__('Mailing method does not exist'));
    }
    return $mailer_instance;
  }

  static function getMailerConfig($mailer = false) {
    if(!$mailer) {
      $mailer = Setting::getValue(self::MAILER_CONFIG_SETTING_NAME);
      if(!$mailer || !isset($mailer['method'])) throw new \Exception(__('Mailer is not configured'));
    }
    if(empty($mailer['frequency'])) {
      $default_settings = Setting::getDefaults();
      $mailer['frequency'] = $default_settings['mta']['frequency'];
    }
    // add additional variables to the mailer object
    $mailer['class'] = 'MailPoet\\Mailer\\Methods\\' . $mailer['method'];
    $mailer['frequency_interval'] =
      (int)$mailer['frequency']['interval'] * self::SENDING_LIMIT_INTERVAL_MULTIPLIER;
    $mailer['frequency_limit'] = (int)$mailer['frequency']['emails'];
    return $mailer;
  }

  function getSender($sender = false) {
    if(empty($sender)) {
      $sender = Setting::getValue('sender', array());
      if(empty($sender['address'])) throw new \Exception(__('Sender name and email are not configured'));
    }
    return array(
      'from_name' => $sender['name'],
      'from_email' => $sender['address'],
      'from_name_email' => sprintf('%s <%s>', $sender['name'], $sender['address'])
    );
  }

  function getReplyTo($reply_to = false) {
    if(!$reply_to) {
      $reply_to = Setting::getValue('reply_to', null);
      if(!$reply_to) {
        $reply_to = array(
          'name' => $this->sender['from_name'],
          'address' => $this->sender['from_email']
        );
      }
    }
    if(empty($reply_to['address'])) {
      $reply_to['address'] = $this->sender['from_email'];
    }
    return array(
      'reply_to_name' => $reply_to['name'],
      'reply_to_email' => $reply_to['address'],
      'reply_to_name_email' => sprintf('%s <%s>', $reply_to['name'], $reply_to['address'])
    );
  }

  function transformSubscriber($subscriber) {
    if(!is_array($subscriber)) return $subscriber;
    if(isset($subscriber['address'])) $subscriber['email'] = $subscriber['address'];
    $first_name = (isset($subscriber['first_name'])) ? $subscriber['first_name'] : '';
    $last_name = (isset($subscriber['last_name'])) ? $subscriber['last_name'] : '';
    if(!$first_name && !$last_name) return $subscriber['email'];
    $subscriber = sprintf('%s %s <%s>', $first_name, $last_name, $subscriber['email']);
    $subscriber = trim(preg_replace('!\s\s+!', ' ', $subscriber));
    return $subscriber;
  }
}