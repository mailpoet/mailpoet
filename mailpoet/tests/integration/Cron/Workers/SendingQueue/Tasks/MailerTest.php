<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Populator;
use MailPoet\Cron\Workers\SendingQueue\Tasks\Mailer as MailerTask;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;

class MailerTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
  }

  public function testConfiguresMailerWhenItConstructs() {
    $mailerFactoryMock = $this->createMock(MailerFactory::class);
    $mailerFactoryMock->expects($this->once())
      ->method('buildMailer')
      ->willReturn($this->createMock(Mailer::class));
    new MailerTask($mailerFactoryMock);
  }

  public function testItCanConfigureMailerWithSenderAndReplyToAddressesFromEmail() {
    $newsletter = new \stdClass();
    $newsletter->senderName = 'Sender';
    $newsletter->senderAddress = 'from@example.com';
    $newsletter->replyToName = 'Reply-to';
    $newsletter->replyToAddress = 'reply-to@example.com';

    $mailerFactoryMock = $this->createMock(MailerFactory::class);
    // First call in constructor
    $mailerFactoryMock->expects($this->at(0))
      ->method('buildMailer')
      ->willReturn($this->createMock(Mailer::class));
    // Second call in custom mailer configuration should be called with sender and reply to from newsletter
    $mailerFactoryMock->expects($this->at(1))
      ->method('buildMailer')
      ->with(
        null,
        ['name' => 'Sender', 'address' => 'from@example.com'],
        ['name' => 'Reply-to', 'address' => 'reply-to@example.com']
      )
      ->willReturn($this->createMock(Mailer::class));
    $mailerTask = new MailerTask($mailerFactoryMock);
    $mailerTask->configureMailer($newsletter);
  }

  public function testItGetsMailerLog() {
    $mailerTask = $this->diContainer->get(MailerTask::class);
    $mailerLog = $mailerTask->getMailerLog();
    expect(is_array($mailerLog))->true();
  }

  public function testItUpdatesMailerLogSentCount() {
    $mailerTask = $this->diContainer->get(MailerTask::class);
    $mailerLog = $mailerTask->getMailerLog();
    expect(array_sum($mailerLog['sent']))->equals(0);
    $mailerLog = $mailerTask->updateSentCount();
    expect(array_sum($mailerLog['sent']))->equals(1);
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
    $mailerTask = new MailerTask($this->diContainer->get(MailerFactory::class));
    expect($mailerTask->getProcessingMethod())->equals('bulk');

    // when using other methods, newsletters should be processed individually
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'PHPMail',
      ]
    );
    $mailerTask = new MailerTask($this->diContainer->get(MailerFactory::class));
    expect($mailerTask->getProcessingMethod())->equals('individual');
  }

  public function testItCanPrepareSubscriberForSending() {
    $subscriber = Subscriber::create();
    $subscriber->email = 'test@example.com';
    $subscriber->firstName = 'John';
    $subscriber->lastName = 'Doe';
    $subscriber->save();
    $mailerTask = $this->diContainer->get(MailerTask::class);
    $preparedSubscriber = $mailerTask->prepareSubscriberForSending($subscriber);
    expect($preparedSubscriber)->equals('John Doe <test@example.com>');
  }

  public function testItCanSend() {
    $phpMailClass = 'MailPoet\Mailer\Methods\PHPMail';
    $mailerMock = Stub::makeEmpty(Mailer::class, [
      'mailerMethod' => Stub::make(
        $phpMailClass,
        ['send' => Expected::exactly(1, function() {
          return ['response' => true];
        })],
        $this
      ),
    ]);
    $mailerFactoryMock = $this->createMock(MailerFactory::class);
    $mailerFactoryMock->expects($this->once())
      ->method('buildMailer')
      ->willReturn($mailerMock);
    // mock mailer instance and ensure that send method is invoked
    $mailerTask = new MailerTask($mailerFactoryMock);
    // send method should return true
    expect($mailerTask->send('Newsletter', 'Subscriber'))->equals(['response' => true]);
  }

  public function _after() {
    parent::_after();
  }
}
