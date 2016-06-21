<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Mailer\Mailer as MailerFactory;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Mailer {
  public $mta_config;
  public $mta_log;

  function __construct() {
    $this->mta_config = $this->getMailerConfig();
    $this->mta_log = $this->getMailerLog();
  }

  function configureMailer(array $newsletter) {
    $sender['address'] = (!empty($newsletter['sender_address'])) ?
      $newsletter['sender_address'] :
      false;
    $sender['name'] = (!empty($newsletter['sender_name'])) ?
      $newsletter['sender_name'] :
      false;
    $reply_to['address'] = (!empty($newsletter['reply_to_address'])) ?
      $newsletter['reply_to_address'] :
      false;
    $reply_to['name'] = (!empty($newsletter['reply_to_name'])) ?
      $newsletter['reply_to_name'] :
      false;
    if(!$sender['address']) {
      $sender = false;
    }
    if(!$reply_to['address']) {
      $reply_to = false;
    }
    $mailer = new MailerFactory($method = false, $sender, $reply_to);
    return $mailer;
  }

  function getMailerConfig() {
    $mta_config = Setting::getValue('mta');
    if(!$mta_config) {
      throw new \Exception(__('Mailer is not configured.'));
    }
    return $mta_config;
  }

  function getMailerLog() {
    $mta_log = Setting::getValue('mta_log');
    if(!$mta_log) {
      $mta_log = array(
        'sent' => 0,
        'started' => time()
      );
      Setting::setValue('mta_log', $mta_log);
    }
    return $mta_log;
  }

  function updateMailerLog() {
    $this->mta_log['sent']++;
    Setting::setValue('mta_log', $this->mta_log);
  }

  function getProcessingMethod() {
    return ($this->mta_config['method'] === 'MailPoet') ?
      'bulk' :
      'individual';
  }

  function prepareSubscriberForSending($mailer, $subscriber) {
    return ($mailer instanceof \MailPoet\Mailer\Mailer) ?
      $mailer->transformSubscriber($subscriber) :
      false;
  }

  function send($mailer, $newsletter, $subscriber) {
    return ($mailer instanceof \MailPoet\Mailer\Mailer) ?
      $mailer->mailer_instance->send($newsletter, $subscriber) :
      false;
  }

  function checkSendingLimit() {
    if($this->mta_config['method'] === 'MailPoet') return;
    $frequency_interval = (int) $this->mta_config['frequency']['interval'] * 60;
    $frequency_limit = (int) $this->mta_config['frequency']['emails'];
    $elapsed_time = time() - (int) $this->mta_log['started'];
    if($this->mta_log['sent'] === $frequency_limit &&
      $elapsed_time <= $frequency_interval
    ) {
      throw new \Exception(__('Sending frequency limit has been reached.'));
    }
    if($elapsed_time > $frequency_interval) {
      $this->mta_log = array(
        'sent' => 0,
        'started' => time()
      );
      Setting::setValue('mta_log', $this->mta_log);
    }
  }
}