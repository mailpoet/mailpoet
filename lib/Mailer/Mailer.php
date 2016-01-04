<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Setting;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Mailer {
  function __construct($mailer = false, $sender = false, $reply_to = false) {
    $this->mailer = $this->getMailer($mailer);
    $this->sender = $this->getSender($sender);
    $this->replyTo = $this->getReplyTo($reply_to);
    $this->mailerInstance = $this->buildMailer();
  }
  
  function send($newsletter, $subscriber) {
    $subscriber = $this->transformSubscriber($subscriber);
    return $this->mailerInstance->send($newsletter, $subscriber);
  }
  
  function buildMailer() {
    switch($this->mailer['method']) {
      case 'AmazonSES':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['region'],
          $this->mailer['access_key'],
          $this->mailer['secret_key'],
          $this->sender['fromNameEmail']
        );
        break;
      case 'ElasticEmail':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->sender['fromEmail'],
          $this->sender['fromName']
        );
        break;
      case 'MailGun':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['domain'],
          $this->mailer['api_key'],
          $this->sender['fromNameEmail']
        );
        break;
      case 'MailPoet':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['mailpoet_api_key'],
          $this->sender['fromEmail'],
          $this->sender['fromName']
        );
        break;
      case 'Mandrill':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->sender['fromEmail'],
          $this->sender['fromName']
        );
        break;
      case 'SendGrid':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->sender['fromEmail'],
          $this->sender['fromName']
        );
        break;
      case 'WPMail':
        $mailerInstance = new $this->mailer['class'](
          $this->sender['fromEmail'],
          $this->sender['fromName']
        );
        break;
      case 'SMTP':
        $mailerInstance = new $this->mailer['class'](
          $this->mailer['host'],
          $this->mailer['port'],
          $this->mailer['authentication'],
          $this->mailer['login'],
          $this->mailer['password'],
          $this->mailer['encryption'],
          $this->sender['fromEmail'],
          $this->sender['fromName']
        );
        break;
      default:
        throw new \Exception(__('Mailing method does not exist.'));
        break;
    }
    return $mailerInstance;
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
      'fromName' => $sender['name'],
      'fromEmail' => $sender['address'],
      'fromNameEmail' => sprintf('%s <%s>', $sender['name'], $sender['address'])
    );
  }

  function getReplyTo($replyTo = false) {
    if(!$replyTo) {
      $replyTo = Setting::getValue('replyTo', null);
      if(!$replyTo) {
        $replyTo = array(
          'name' => $this->sender['fromName'],
          'address' => $this->sender['fromEmail']
        );
      }
    }
    return array(
      'replyToName' => $replyTo['name'],
      'replyToEmail' => $replyTo['address'],
      'replyToNameEmail' => sprintf('%s <%s>', $replyTo['name'], $replyTo['address'])
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