<?php
use Codeception\Util\Stub;
use MailPoet\Config\Populator;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class MailerTaskTest extends MailPoetTest {
  public $mailer_task;
  public $sender;

  function _before() {
    $wp_users = get_users();
    wp_set_current_user($wp_users[0]->ID);
    $populator = new Populator();
    $populator->up();
    $this->mailer_task = new MailerTask();
    $this->sender = Setting::getValue('sender');
  }

  function testConfiguresMailerWhenItConstructs() {
    expect($this->mailer_task->mailer instanceof MailPoet\Mailer\Mailer)->true();
  }

  function testItCanConfigureMailerWithSenderAndReplyToAddresses() {
    $newsletter = new stdClass();

    // when no sender/reply-to information is set, use the sender information
    // from Settings
    $mailer = $this->mailer_task->configureMailer($newsletter);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->sender['from_email'])->equals($this->sender['address']);
    expect($mailer->reply_to['reply_to_name'])->equals($this->sender['name']);
    expect($mailer->reply_to['reply_to_email'])->equals($this->sender['address']);
    $newsletter->sender_name = 'Sender';
    $newsletter->sender_address = 'from@example.com';
    $newsletter->reply_to_name = 'Reply-to';
    $newsletter->reply_to_address = 'reply-to@example.com';

    // when newsletter's sender/reply-to information is available, use that
    // to configure mailer
    $mailer = $this->mailer_task->configureMailer($newsletter);
    expect($mailer->sender['from_name'])->equals($newsletter->sender_name);
    expect($mailer->sender['from_email'])->equals($newsletter->sender_address);
    expect($mailer->reply_to['reply_to_name'])->equals($newsletter->reply_to_name);
    expect($mailer->reply_to['reply_to_email'])->equals($newsletter->reply_to_address);
  }

  function testItGetsMailerLog() {
    $mailer_log = $this->mailer_task->getMailerLog();
    expect(is_array($mailer_log))->true();
  }

  function testItUpdatesMailerLogSentCount() {
    $mailer_log = $this->mailer_task->getMailerLog();
    expect($mailer_log['sent'])->equals(0);
    $mailer_log = $this->mailer_task->updateSentCount();
    expect($mailer_log['sent'])->equals(1);
  }

  function testItGetsProcessingMethod() {
    // when using MailPoet method, newsletters should be processed in bulk
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      )
    );
    $mailer_task = new MailerTask();
    expect($mailer_task->getProcessingMethod())->equals('bulk');

    // when using other methods, newsletters should be processed individually
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'PHPMail'
      )
    );
    $mailer_task = new MailerTask();
    expect($mailer_task->getProcessingMethod())->equals('individual');
  }

  function testItCanPrepareSubscriberForSending() {
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->first_name = 'John';
    $subscriber->last_name = 'Doe';
    $subscriber->save();
    $prepared_subscriber = $this->mailer_task->prepareSubscriberForSending($subscriber);
    expect($prepared_subscriber)->equals('John Doe <test@example.com>');
  }

  function testItCanSend() {
    $php_mail_class = 'MailPoet\Mailer\Methods\PHPMail';
    Setting::setValue(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      array(
        'method' => 'PHPMail'
      )
    );
    // mock mailer instance and ensure that send method is invoked
    $mailer_task = new MailerTask(
      (object)array(
        'mailer_instance' => Stub::make(
          $php_mail_class,
          array('send' => Stub::exactly(1, function($newsletter, $subscriber) { return true; }))
        )
      )
    );
    // mailer instance should be properly configured
    expect($mailer_task->mailer->mailer_instance instanceof $php_mail_class)
      ->true();
    // send method should return true
    expect($mailer_task->send('Newsletter', 'Subscriber'))->true();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}