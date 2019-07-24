<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Mailer\Mailer as MailerFactory;
use MailPoet\Mailer\MailerLog;

if (!defined('ABSPATH')) exit;

class Mailer {
  public $mailer;

  function __construct($mailer = false) {
    $this->mailer = ($mailer) ? $mailer : $this->configureMailer();
  }

  function configureMailer($newsletter = null) {
    $sender['address'] = (!empty($newsletter->sender_address)) ?
      $newsletter->sender_address :
      false;
    $sender['name'] = (!empty($newsletter->sender_name)) ?
      $newsletter->sender_name :
      false;
    $reply_to['address'] = (!empty($newsletter->reply_to_address)) ?
      $newsletter->reply_to_address :
      false;
    $reply_to['name'] = (!empty($newsletter->reply_to_name)) ?
      $newsletter->reply_to_name :
      false;
    if (!$sender['address']) {
      $sender = false;
    }
    if (!$reply_to['address']) {
      $reply_to = false;
    }
    $this->mailer = new MailerFactory();
    $this->mailer->init($method = false, $sender, $reply_to);
    return $this->mailer;
  }

  function getMailerLog() {
    return MailerLog::getMailerLog();
  }

  function updateSentCount() {
    return MailerLog::incrementSentCount();
  }

  function getProcessingMethod() {
    return ($this->mailer->mailer_config['method'] === MailerFactory::METHOD_MAILPOET) ?
      'bulk' :
      'individual';
  }

  function prepareSubscriberForSending($subscriber) {
    return $this->mailer->formatSubscriberNameAndEmailAddress($subscriber);
  }

  function sendBulk($prepared_newsletters, $prepared_subscribers, $extra_params = []) {
    if ($this->getProcessingMethod() === 'individual') {
      throw new \LogicException('Trying to send a batch with individual processing method');
    }
    return $this->mailer->mailer_instance->send(
      $prepared_newsletters,
      $prepared_subscribers,
      $extra_params
    );
  }

  function send($prepared_newsletter, $prepared_subscriber, $extra_params = []) {
    if ($this->getProcessingMethod() === 'bulk') {
      throw new \LogicException('Trying to send an individual email with a bulk processing method');
    }
    return $this->mailer->mailer_instance->send(
      $prepared_newsletter,
      $prepared_subscriber,
      $extra_params
    );
  }
}
