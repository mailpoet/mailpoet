<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Populator;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Features\FeaturesController;
use MailPoet\Form\FormFactory;
use MailPoet\Form\FormsRepository;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Subscriber;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class MailerTest extends \MailPoetTest {
  /** @var MailerTask */
  public $mailerTask;
  public $sender;
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $this->settings = SettingsController::getInstance();
    $referralDetector = new ReferralDetector(WPFunctions::get(), $this->settings);
    $featuresController = Stub::makeEmpty(FeaturesController::class);
    $populator = new Populator(
      $this->settings,
      WPFunctions::get(),
      new Captcha,
      $referralDetector,
      $featuresController,
      $this->diContainer->get(FormsRepository::class),
      $this->diContainer->get(FormFactory::class)
    );
    $populator->up();
    $this->mailerTask = new MailerTask();
    $this->sender = $this->settings->get('sender');
  }

  public function testConfiguresMailerWhenItConstructs() {
    expect($this->mailerTask->mailer instanceof \MailPoet\Mailer\Mailer)->true();
  }

  public function testItCanConfigureMailerWithSenderAndReplyToAddresses() {
    $newsletter = new \stdClass();

    // when no sender/reply-to information is set, use the sender information
    // from Settings
    $mailer = $this->mailerTask->configureMailer($newsletter);
    expect($mailer->sender['from_name'])->equals($this->sender['name']);
    expect($mailer->sender['from_email'])->equals($this->sender['address']);
    expect($mailer->replyTo['reply_to_name'])->equals($this->sender['name']);
    expect($mailer->replyTo['reply_to_email'])->equals($this->sender['address']);
    $newsletter->senderName = 'Sender';
    $newsletter->senderAddress = 'from@example.com';
    $newsletter->replyToName = 'Reply-to';
    $newsletter->replyToAddress = 'reply-to@example.com';

    // when newsletter's sender/reply-to information is available, use that
    // to configure mailer
    $mailer = $this->mailerTask->configureMailer($newsletter);
    expect($mailer->sender['from_name'])->equals($newsletter->senderName);
    expect($mailer->sender['from_email'])->equals($newsletter->senderAddress);
    expect($mailer->replyTo['reply_to_name'])->equals($newsletter->replyToName);
    expect($mailer->replyTo['reply_to_email'])->equals($newsletter->replyToAddress);
  }

  public function testItGetsMailerLog() {
    $mailerLog = $this->mailerTask->getMailerLog();
    expect(is_array($mailerLog))->true();
  }

  public function testItUpdatesMailerLogSentCount() {
    $mailerLog = $this->mailerTask->getMailerLog();
    expect($mailerLog['sent'])->equals(0);
    $mailerLog = $this->mailerTask->updateSentCount();
    expect($mailerLog['sent'])->equals(1);
  }

  public function testItGetsProcessingMethod() {
    // when using MailPoet method, newsletters should be processed in bulk
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => 'some_key',
      ]
    );
    $mailerTask = new MailerTask();
    expect($mailerTask->getProcessingMethod())->equals('bulk');

    // when using other methods, newsletters should be processed individually
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'PHPMail',
      ]
    );
    $mailerTask = new MailerTask();
    expect($mailerTask->getProcessingMethod())->equals('individual');
  }

  public function testItCanPrepareSubscriberForSending() {
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->firstName = 'John';
    $subscriber->lastName = 'Doe';
    $subscriber->save();
    $preparedSubscriber = $this->mailerTask->prepareSubscriberForSending($subscriber);
    expect($preparedSubscriber)->equals('John Doe <test@example.com>');
  }

  public function testItCanSend() {
    $phpMailClass = 'MailPoet\Mailer\Methods\PHPMail';
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'PHPMail',
      ]
    );
    // mock mailer instance and ensure that send method is invoked
    $mailerTask = new MailerTask(
      (object)[
        'mailerInstance' => Stub::make(
          $phpMailClass,
          ['send' => Expected::exactly(1, function() {
              return true;
          })],
          $this
        ),
        'mailerConfig' => [
          'method' => null,
        ],
      ]
    );
    // mailer instance should be properly configured
    expect($mailerTask->mailer->mailerInstance instanceof $phpMailClass)
      ->true();
    // send method should return true
    expect($mailerTask->send('Newsletter', 'Subscriber'))->true();
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
