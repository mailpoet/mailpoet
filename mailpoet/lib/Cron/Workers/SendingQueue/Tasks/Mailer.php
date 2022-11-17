<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Mailer\Mailer as MailerInstance;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\Methods\MailPoet;

class Mailer {
  /** @var MailerFactory */
  private $mailerFactory;

  /** @var MailerInstance */
  private $mailer;

  public function __construct(
    MailerFactory $mailerFactory
  ) {
    $this->mailerFactory = $mailerFactory;
    $this->mailer = $this->configureMailer();
  }

  public function configureMailer($newsletter = null) {
    $sender['address'] = (!empty($newsletter->senderAddress)) ?
      $newsletter->senderAddress :
      null;
    $sender['name'] = (!empty($newsletter->senderName)) ?
      $newsletter->senderName :
      null;
    $replyTo['address'] = (!empty($newsletter->replyToAddress)) ?
      $newsletter->replyToAddress :
      null;
    $replyTo['name'] = (!empty($newsletter->replyToName)) ?
      $newsletter->replyToName :
      null;
    if (!$sender['address']) {
      $sender = null;
    }
    if (!$replyTo['address']) {
      $replyTo = null;
    }
    $this->mailer = $this->mailerFactory->buildMailer(null, $sender, $replyTo);
    return $this->mailer;
  }

  public function getMailerLog() {
    return MailerLog::getMailerLog();
  }

  public function updateSentCount() {
    return MailerLog::incrementSentCount();
  }

  public function getProcessingMethod() {
    return ($this->mailer->mailerMethod instanceof MailPoet) ?
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
    return $this->mailer->mailerMethod->send(
      $preparedNewsletters,
      $preparedSubscribers,
      $extraParams
    );
  }

  public function send($preparedNewsletter, $preparedSubscriber, $extraParams = []) {
    if ($this->getProcessingMethod() === 'bulk') {
      throw new \LogicException('Trying to send an individual email with a bulk processing method');
    }
    return $this->mailer->mailerMethod->send(
      $preparedNewsletter,
      $preparedSubscriber,
      $extraParams
    );
  }
}
