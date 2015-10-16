<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Mailer {
  function __construct() {
    $this->fromName = $this->getSetting('from_name');
    $this->fromEmail = $this->getSetting('from_address');
    $this->mailer = $this->getSetting('mailer');
    $this->from = sprintf('%s <%s>', $this->fromName, $this->fromEmail);
  }

  function send($newsletter, $subscriber) {
    $subscriber = $this->transformSubscriber($subscriber);
    $mailer = $this->buildMailer();
    return wp_send_json($mailer->send($newsletter, $subscriber));
  }

  function buildMailer() {
    switch ($this->mailer['name']) {
    case 'AmazonSES':
      $mailer = new $this->mailer['class'](
        $this->mailer['region'],
        $this->mailer['access_key'],
        $this->mailer['secret_key'],
        $this->from
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
        $this->from
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
    case 'SMTP':
      $mailer = new $this->mailer['class'](
        $this->mailer['host'],
        $this->mailer['port'],
        $this->mailer['authentication'],
        $this->mailer['encryption'],
        $this->fromEmail,
        $this->fromName);
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
    if($setting === 'mailer') {
      $mailers = array(
        array(
          'name' => 'AmazonSES',
          'type' => 'API',
          'access_key' => 'AKIAJM6Y5HMGXBLDNSRA',
          'secret_key' => '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',
          'region' => 'us-east-1'
        ),
        array(
          'name' => 'ElasticEmail',
          'type' => 'API',
          'api_key' => '997f1f7f-41de-4d7f-a8cb-86c8481370fa'
        ),
        array(
          'name' => 'MailGun',
          'type' => 'API',
          'api_key' => 'key-6cf5g5qjzenk-7nodj44gdt8phe6vam2',
          'domain' => 'mrcasual.com'
        ),
        array(
          'name' => 'MailPoet',
          'api_key' => 'dhNSqj1XHkVltIliyQDvMiKzQShOA5rs0m_DdRUVZHU'
        ),
        array(
          'name' => 'Mandrill',
          'type' => 'API',
          'api_key' => '692ys1B7REEoZN7R-dYwNA'
        ),
        array(
          'name' => 'SendGrid',
          'type' => 'API',
          'api_key' => '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'
        ),
        array(
          'name' => 'SMTP',
          'host' => 'email-smtp.us-west-2.amazonaws.com',
          'port' => 587,
          'authentication' => array(
            'login' => 'AKIAIGPBLH6JWG5VCBQQ',
            'password' => 'AudVHXHaYkvr54veCzqiqOxDiMMyfQW3/V6F1tYzGXY3'
          ),
          'encryption' => 'tls'
        ),
        array(
          'name' => 'WPMail'
        )
      );
      $mailer = $mailers[array_rand($mailers)];
      $mailer['class'] = 'MailPoet\\Mailer\\' .
        ((isset($mailer['type'])) ?
          $mailer['type'] . '\\' . $mailer['name'] :
          $mailer['name']
        );
      return $mailer;
    }
    if($setting === 'from_name') return 'Sender';
    if($setting === 'from_address') return 'staff@mailpoet.com';
    return Setting::where('name', $setting)
      ->findOne()->value;
  }
}