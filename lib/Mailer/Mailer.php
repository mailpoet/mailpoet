<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Mailer {
  public $mailer;
  public $sender;
  public $reply_to;
  public $mailer_instance;

  function __construct($mailer = false, $sender = false, $reply_to = false) {
    $this->mailer = $this->getMailer($mailer);
    $this->sender = $this->getSender($sender);
    $this->reply_to = $this->getReplyTo($reply_to);
    $this->mailer_instance = $this->buildMailer();
  }

  function send($newsletter, $subscriber) {
    $subscriber = $this->transformSubscriber($subscriber);
    return $this->mailer_instance->send($newsletter, $subscriber);
  }

  function buildMailer() {
    switch($this->mailer['method']) {
    case 'AmazonSES':
      $mailer_instance = new $this->mailer['class'](
        $this->mailer['region'],
        $this->mailer['access_key'],
        $this->mailer['secret_key'],
        $this->sender['from_name_email']
      );
    break;
    case 'ElasticEmail':
      $mailer_instance = new $this->mailer['class'](
        $this->mailer['api_key'],
        $this->sender['from_email'],
        $this->sender['from_name']
      );
    break;
    case 'MailGun':
      $mailer_instance = new $this->mailer['class'](
        $this->mailer['domain'],
        $this->mailer['api_key'],
        $this->sender['from_name_email']
      );
    break;
    case 'MailPoet':
      $mailer_instance = new $this->mailer['class'](
        $this->mailer['mailpoet_api_key'],
        $this->sender['from_email'],
        $this->sender['from_name']
      );
    break;
    case 'SendGrid':
      $mailer_instance = new $this->mailer['class'](
        $this->mailer['api_key'],
        $this->sender['from_email'],
        $this->sender['from_name']
      );
    break;
    case 'WPMail':
      $mailer_instance = new $this->mailer['class'](
        $this->sender['from_email'],
        $this->sender['from_name']
      );
    break;
    case 'SMTP':
      $mailer_instance = new $this->mailer['class'](
        $this->mailer['host'],
        $this->mailer['port'],
        $this->mailer['authentication'],
        $this->mailer['login'],
        $this->mailer['password'],
        $this->mailer['encryption'],
        $this->sender['from_email'],
        $this->sender['from_name']
      );
    break;
    default:
      throw new \Exception(__('Mailing method does not exist.'));
    break;
    }
    return $mailer_instance;
  }

  function getMailer($mailer = false) {
    if(!$mailer) {
      $mailer = Setting::getValue('mta', null);
      if(!$mailer || !isset($mailer['method'])) throw new \Exception(__('Mailer is not configured.'));
    }
    $mailer['class'] = 'MailPoet\\Mailer\\Methods\\' . $mailer['method'];
    return $mailer;
  }

  function getSender($sender = false) {
    if(!$sender) {
      $sender = Setting::getValue('sender', null);
      if(!$sender) throw new \Exception(__('Sender name and email are not configured.'));
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