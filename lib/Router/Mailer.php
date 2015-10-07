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
    $mailer = $this->configureMailer();
    return wp_send_json($mailer->send($newsletter, $subscriber));
  }

  function configureMailer() {
    switch ($this->mailer['name']) {
    case 'AmazonSES':
      $mailer = new $this->mailer['class']($this->mailer['region'], $this->mailer['access_key'], $this->mailer['secret_key'], $this->from);
    break;
    case 'ElasticEmail':
      $mailer = new $this->mailer['class']($this->mailer['api_key'], $this->fromEmail, $this->fromName);
    break;
    case 'MailGun':
      $mailer = new $this->mailer['class']($this->mailer['domain'], $this->mailer['api_key'], $this->from);
    break;
    case 'Mandrill':
      $mailer = new $this->mailer['class']($this->mailer['api_key'], $this->fromEmail, $this->fromName);
    break;
    case 'SendGrid':
      $mailer = new $this->mailer['class']($this->mailer['api_key'], $this->from);
    break;
    }
    return $mailer;
  }

  function transformSubscriber($subscriber) {
    if(!is_array($subscriber)) return $subscriber;
    $first_name = (isset($subscriber['first_name'])) ? $subscriber['first_name'] : '';
    $last_name = (isset($subscriber['last_name'])) ? $subscriber['last_name'] : '';
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
          'region' => 'us-east-1',
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
          'name' => 'Mandrill',
          'type' => 'API',
          'api_key' => '692ys1B7REEoZN7R-dYwNA'
        ),
        array(
          'name' => 'SendGrid',
          'type' => 'API',
          'api_key' => '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'
        )
      );
      $mailer = $mailers[array_rand($mailers)];
      return array_merge($mailer, array('class' => sprintf('MailPoet\\Mailer\\%s\\%s', $mailer['type'], $mailer['name'])));
    }
    if($setting === 'from_name') return 'Sender';
    if($setting === 'from_address') return 'mailpoet-test1@mailinator.com';
    return Setting::where('name', $setting)
      ->findOne()->value;
  }
}