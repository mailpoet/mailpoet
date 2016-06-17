<?php
namespace MailPoet\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Mailer\Mailer as MailerFactory;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Mailer {
  static function configureMailer(array $newsletter) {
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

  static function getMailerConfig() {
    $mta_config = Setting::getValue('mta');
    if(!$mta_config) {
      throw new \Exception(__('Mailer is not configured.'));
    }
    return $mta_config;
  }

  static function updateMailerLog($mta_log) {
    $mta_log['sent']++;
    Setting::setValue('mta_log', $mta_log);
    return $mta_log;
  }

  static function getMailerLog() {
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

  static function getProcessingMethod($mta_config) {
    return ($mta_config['method'] === 'MailPoet') ?
      'processBulkSubscribers' :
      'processIndividualSubscriber';
  }

  static function prepareSubscriberForSending($mailer, $subscriber) {
    return ($mailer instanceof \MailPoet\Mailer\Mailer) ?
      $mailer->transformSubscriber($subscriber) :
      false;
  }

  static function send($mailer, $newsletter, $subscriber) {
    return ($mailer instanceof \MailPoet\Mailer\Mailer) ?
      $mailer->mailer_instance->send($newsletter, $subscriber) :
      false;
  }

  static function checkSendingLimit($mta_config, $mta_log) {
    $frequency_interval = (int) $mta_config['frequency']['interval'] * 60;
    $frequency_limit = (int) $mta_config['frequency']['emails'];
    $elapsed_time = time() - (int) $mta_log['started'];
    if($mta_log['sent'] === $frequency_limit &&
      $elapsed_time <= $frequency_interval
    ) {
      throw new \Exception(__('Sending frequency limit has been reached.'));
    }
    if($elapsed_time > $frequency_interval) {
      $mta_log = array(
        'sent' => 0,
        'started' => time()
      );
      Setting::setValue('mta_log', $mta_log);
    }
    return;
  }
}