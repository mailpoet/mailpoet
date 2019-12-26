<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Mailer\Mailer as MailerFactory;
use MailPoet\Mailer\MailerLog;

class Mailer {
  public $mailer;

  public function __construct($mailer = false) {
    $this->mailer = ($mailer) ? $mailer : $this->configureMailer();
  }

  public function configureMailer($newsletter = null) {
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

  public function getMailerLog() {
    return MailerLog::getMailerLog();
  }

  public function updateSentCount() {
    return MailerLog::incrementSentCount();
  }

  public function getProcessingMethod() {
    return ($this->mailer->mailer_config['method'] === MailerFactory::METHOD_MAILPOET) ?
      'bulk' :
      'individual';
  }

  public function prepareSubscriberForSending($subscriber) {
    return $this->mailer->formatSubscriberNameAndEmailAddress($subscriber);
  }

  public function sendBulk($prepared_newsletters, $prepared_subscribers, $extra_params = []) {
    if ($this->getProcessingMethod() === 'individual') {
      throw new \LogicException('Trying to send a batch with individual processing method');
    }
    return $this->mailer->mailer_instance->send(
      $prepared_newsletters,
      $prepared_subscribers,
      $extra_params
    );
  }

  public function send($prepared_newsletter, $prepared_subscriber, $extra_params = []) {
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
