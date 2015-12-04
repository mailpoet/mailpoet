<?php
namespace MailPoet\Router;

use MailPoet\Models\Setting;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Mailer {
  function __construct($httpRequest = true) {
    $this->mailerType = array(
      'AmazonSES' => 'API',
      'ElasticEmail' => 'API',
      'MailGun' => 'API',
      'Mandrill' => 'API',
      'SendGrid' => 'API',
      'MailPoet' => null,
      'SMTP' => null,
      'WPMail' => null
    );
    if(!$httpRequest) {
      list($this->fromName, $this->fromEmail, $this->fromNameEmail)
        = $this->getSetting('sender');
      $this->mailer = $this->getSetting('mailer');
    }
  }

  function send($data) {
    $subscriber = $this->transformSubscriber($data['subscriber']);
    list($fromName, $fromEmail, $fromNameEmail)
      = $this->getSetting('sender');
    $data['mailer']['class'] = 'MailPoet\\Mailer\\' .
      (($this->mailerType[$data['mailer']['method']]) ?
        $this->mailerType[$data['mailer']['method']] . '\\' . $data['mailer']['method'] :
        $data['mailer']['method']
      );
    $mailer = $this->buildMailer(
      $data['mailer'],
      $fromName,
      $fromEmail,
      $fromNameEmail
    );
    if(!empty($newsletter['sender_address']) &&
      !empty($newsletter['sender_name'])
    ) {
      $mailer->fromName = $newsletter['sender_name'];
      $mailer->fromEmail = $newsletter['sender_address'];
      $mailer->fromNameEmail = sprintf(
        '%s <%s>',
        $mailer->fromName,
        $mailer->fromEmail
      );
    }
    if(!empty($newsletter['reply_to_address']) &&
      !empty($newsletter['reply_to_name'])
    ) {
      $mailer->replyToName = $newsletter['reply_to_name'];
      $mailer->replyToEmail = $newsletter['reply_to_address'];
      $mailer->replyToNameEmail = sprintf(
        '%s <%s>',
        $mailer->replyToName,
        $mailer->replyToEmail
      );
    }
    return $mailer->send($data['newsletter'], $subscriber);
  }

  function buildMailer($mailer = false, $fromName = false, $fromEmail = false, $fromNameEmail = false) {
    if(!$mailer) $mailer = $this->mailer;
    if(!$fromName) $fromName = $this->fromName;
    if(!$fromEmail) $fromEmail = $this->fromEmail;
    if(!$fromNameEmail) $fromNameEmail = $this->fromNameEmail;
    switch($mailer['method']) {
      case 'AmazonSES':
        $mailerInstance = new $mailer['class'](
          $mailer['region'],
          $mailer['access_key'],
          $mailer['secret_key'],
          $fromNameEmail
        );
        break;
      case 'ElasticEmail':
        $mailerInstance = new $mailer['class'](
          $mailer['api_key'],
          $fromEmail, $fromName
        );
        break;
      case 'MailGun':
        $mailerInstance = new $mailer['class'](
          $mailer['domain'],
          $mailer['api_key'],
          $fromNameEmail
        );
        break;
      case 'MailPoet':
        $mailerInstance = new $mailer['class'](
          $mailer['api_key'],
          $fromEmail,
          $fromName
        );
        break;
      case 'Mandrill':
        $mailerInstance = new $mailer['class'](
          $mailer['api_key'],
          $fromEmail, $fromName
        );
        break;
      case 'SendGrid':
        $mailerInstance = new $mailer['class'](
          $mailer['api_key'],
          $fromEmail,
          $fromName
        );
        break;
      case 'WPMail':
        $mailerInstance = new $mailer['class'](
          $fromEmail,
          $fromName
        );
        break;
      case 'SMTP':
        $mailerInstance = new $mailer['class'](
          $mailer['host'],
          $mailer['port'],
          $mailer['authentication'],
          $mailer['encryption'],
          $fromEmail,
          $fromName
        );
        break;
      default:
        throw new \Exception('Mailing method does not exist.');
        break;
    }
    return $mailerInstance;
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

  function getSetting($setting) {
    switch($setting) {
      case 'mailer':
        $mailer = Setting::getValue('mta', null);
        if(!$mailer || !isset($mailer['method'])) throw new \Exception('Mailing method is not configured.');
        $mailer['class'] = 'MailPoet\\Mailer\\' .
          (($this->mailerType[$mailer['method']]) ?
            $this->mailerType[$mailer['method']] . '\\' . $mailer['method'] :
            $mailer['method']
          );
        return $mailer;
        break;
      case 'sender':
        $sender = Setting::getValue($setting, null);
        if(!$sender) throw new \Exception('Sender name and email are not configured.');
        return array(
          $sender['name'],
          $sender['address'],
          sprintf('%s <%s>', $sender['name'], $sender['address'])
        );
        break;
      default:
        return Setting::getValue($setting, null);
        break;
    }
  }
}