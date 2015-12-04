<?php
namespace MailPoet\Router;

use MailPoet\Models\Setting;

require_once(ABSPATH . 'wp-includes/pluggable.php');

if(!defined('ABSPATH')) exit;

class Mailer {
  function __construct() {
    list($this->fromName, $this->fromEmail, $this->fromNameEmail)
      = $this->getSetting('sender');
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

    $this->mailer = $this->getSetting('mailer');
  }

  function send($newsletter, $subscriber) {
    $subscriber = $this->transformSubscriber($subscriber);
    $mailer = $this->buildMailer();
    return $mailer->send($newsletter, $subscriber);
  }

  function buildMailer() {
    switch($this->mailer['method']) {
      case 'AmazonSES':
        $mailer = new $this->mailer['class'](
          $this->mailer['region'],
          $this->mailer['access_key'],
          $this->mailer['secret_key'],
          $this->fromNameEmail
        );
        break;
      case 'ElasticEmail':
        $mailer = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->fromEmail, $this->fromName
        );
        break;
      case 'MailGun':
        $mailer = new $this->mailer['class'](
          $this->mailer['domain'],
          $this->mailer['api_key'],
          $this->fromNameEmail
        );
        break;
      case 'MailPoet':
        $mailer = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->fromEmail,
          $this->fromName
        );
        break;
      case 'Mandrill':
        $mailer = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->fromEmail, $this->fromName
        );
        break;
      case 'SendGrid':
        $mailer = new $this->mailer['class'](
          $this->mailer['api_key'],
          $this->fromEmail,
          $this->fromName
        );
        break;
      case 'WPMail':
        $mailer = new $this->mailer['class'](
          $this->fromEmail,
          $this->fromName
        );
        break;
      case 'SMTP':
        $mailer = new $this->mailer['class'](
          $this->mailer['host'],
          $this->mailer['port'],
          $this->mailer['authentication'],
          $this->mailer['encryption'],
          $this->fromEmail,
          $this->fromName
        );
        break;
      default:
        throw new \Exception('Mailing method does not exist.');
        break;
    }
    return $mailer;
  }

  function transformSubscriber($subscriber) {
    if(!is_array($subscriber)) return $subscriber;
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
        break;;
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