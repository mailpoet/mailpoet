<?php

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Mailer\Mailer as MailerFactory;
use MailPoet\Mailer\MailerLog;

class Mailer {
  public $mailer;

  public function __construct(
    $mailer = false
  ) {
    $this->mailer = ($mailer) ? $mailer : $this->configureMailer();
  }

  public function configureMailer($newsletter = null) {
    $sender['address'] = (!empty($newsletter->senderAddress)) ?
      $newsletter->senderAddress :
      false;
    $sender['name'] = (!empty($newsletter->senderName)) ?
      $newsletter->senderName :
      false;
    $replyTo['address'] = (!empty($newsletter->replyToAddress)) ?
      $newsletter->replyToAddress :
      false;
    $replyTo['name'] = (!empty($newsletter->replyToName)) ?
      $newsletter->replyToName :
      false;
    if (!$sender['address']) {
      $sender = false;
    }
    if (!$replyTo['address']) {
      $replyTo = false;
    }
    $this->mailer = new MailerFactory();
    $this->mailer->init($method = false, $sender, $replyTo);
    return $this->mailer;
  }

  public function getMailerLog() {
    return MailerLog::getMailerLog();
  }

  public function updateSentCount() {
    return MailerLog::incrementSentCount();
  }

  public function getProcessingMethod() {
    return ($this->mailer->mailerConfig['method'] === MailerFactory::METHOD_MAILPOET) ?
      'bulk' :
      'individual';
  }

  public function prepareSubscriberForSending($subscriber) {
    return $this->mailer->formatSubscriberNameAndEmailAddress($subscriber);
  }

  public function sendBulk($preparedNewsletters, $preparedSubscribers, $extraParams = []) {
    if ($this->getProcessingMethod() === 'individual') {
      throw new \LogicException('Trying to send a batch with individual processing method');
    }
    return $this->mailer->mailerInstance->send(
      $preparedNewsletters,
      $preparedSubscribers,
      $extraParams
    );
  }

  public function send($preparedNewsletter, $preparedSubscriber, $extraParams = []) {
    if ($this->getProcessingMethod() === 'bulk') {
      throw new \LogicException('Trying to send an individual email with a bulk processing method');
    }
    return $this->mailer->mailerInstance->send(
      $preparedNewsletter,
      $preparedSubscriber,
      $extraParams
    );
  }
}
