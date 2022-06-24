<?php declare(strict_types=1);

namespace MailPoet\Test\Mailer;

use MailPoet\InvalidStateException;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\Methods\AmazonSES;
use MailPoet\Mailer\Methods\MailPoet;
use MailPoet\Mailer\Methods\PHPMail;
use MailPoet\Mailer\Methods\SendGrid;
use MailPoet\Mailer\Methods\SMTP;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class MailerFactoryTest extends \MailPoetTest {
  /** @var array */
  private $mtaConfigs = [
    Mailer::METHOD_AMAZONSES => [
      'method' => 'AmazonSES',
      'region' => 'us-west-2',
      'access_key' => '1234567890',
      'secret_key' => 'abcdefghijk',
    ],
    Mailer::METHOD_MAILPOET => [
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'abcdefghijk',
    ],
    Mailer::METHOD_SENDGRID => [
      'method' => 'SendGrid',
      'api_key' => 'abcdefghijk',
    ],
    Mailer::METHOD_PHPMAIL => [
      'method' => 'PHPMail',
    ],
    Mailer::METHOD_SMTP => [
      'method' => 'SMTP',
      'host' => 'example.com',
      'port' => 25,
      'authentication' => true,
      'login' => 'username',
      'password' => 'password',
      'encryption' => 'tls',
    ],
  ];

  /** @var array */
  private $sender = [
    'name' => 'Sender',
    'address' => 'sender@email.com',
    ];

  /** @var array */
  private $replyTo = [
    'name' => 'Reply To',
    'address' => 'reply@email.com',
  ];

  /** @var string */
  private $returnPath = 'bounce@test.com';

  /** @var SettingsController */
  private $settings;

  /** @var MailerFactory */
  private $factory;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->factory = new MailerFactory($this->settings, $this->diContainer->get(WPFunctions::class));
    $this->settings->set('sender', $this->sender);
    $this->settings->set('reply_to', $this->replyTo);
    $this->settings->set('bounce.address', $this->returnPath);
    $this->settings->set('mta', $this->mtaConfigs[Mailer::METHOD_PHPMAIL]);
  }

  public function testItRequiresMailerMethodToBeConfigured() {
    // reset mta settings so that we have no default mailer
    $this->settings->set('mta', null);
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('Mailer is not configured.');
    $this->factory->getDefaultMailer();
  }

  public function testItRequiresSenderToBeConfigured() {
    // reset settings so that we have no sender
    $this->settings->set('sender', null);
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('Sender name and email are not configured.');
    $this->factory->getDefaultMailer();
  }

  public function testItThrowsUnknownMailerException() {
    $this->settings->set('mta', ['method' => 'unknown']);
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage('Mailing method does not exist.');
    $this->factory->getDefaultMailer();
  }

  public function testItCanBuildCorrectMailerMethodsBasedOnConfig() {
    $this->settings->set('mta', $this->mtaConfigs[Mailer::METHOD_PHPMAIL]);
    $mailer = $this->factory->getDefaultMailer();
    $this->assertInstanceOf(PHPMail::class, $mailer->mailerMethod);

    $this->factory = new MailerFactory($this->settings, $this->diContainer->get(WPFunctions::class));
    $this->settings->set('mta', $this->mtaConfigs[Mailer::METHOD_AMAZONSES]);
    $mailer = $this->factory->getDefaultMailer();
    $this->assertInstanceOf(AmazonSES::class, $mailer->mailerMethod);

    $this->factory = new MailerFactory($this->settings, $this->diContainer->get(WPFunctions::class));
    $this->settings->set('mta', $this->mtaConfigs[Mailer::METHOD_MAILPOET]);
    $mailer = $this->factory->getDefaultMailer();
    $this->assertInstanceOf(MailPoet::class, $mailer->mailerMethod);

    $this->factory = new MailerFactory($this->settings, $this->diContainer->get(WPFunctions::class));
    $this->settings->set('mta', $this->mtaConfigs[Mailer::METHOD_SMTP]);
    $mailer = $this->factory->getDefaultMailer();
    $this->assertInstanceOf(SMTP::class, $mailer->mailerMethod);

    $this->factory = new MailerFactory($this->settings, $this->diContainer->get(WPFunctions::class));
    $this->settings->set('mta', $this->mtaConfigs[Mailer::METHOD_SENDGRID]);
    $mailer = $this->factory->getDefaultMailer();
    $this->assertInstanceOf(SendGrid::class, $mailer->mailerMethod);
  }

  public function testItUsesProcessedSenderDataFromSettings() {
    $mailer = $this->factory->getDefaultMailer();
    $mailerMethod = $mailer->mailerMethod;
    $this->assertInstanceOf(PHPMail::class, $mailerMethod);
    expect($mailerMethod->sender)->equals([
      'from_name' => 'Sender',
      'from_email' => 'sender@email.com',
      'from_name_email' => 'Sender <sender@email.com>',
    ]);
    expect($mailerMethod->replyTo)->equals([
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'reply@email.com',
      'reply_to_name_email' => 'Reply To <reply@email.com>',
    ]);
    expect($mailerMethod->returnPath)->equals($this->returnPath);
  }

  public function testItUsesSenderAsReplyToWhenReplyToIsNotSet() {
    $this->settings->set('reply_to', null);
    $mailer = $this->factory->getDefaultMailer();
    $mailerMethod = $mailer->mailerMethod;
    $this->assertInstanceOf(PHPMail::class, $mailerMethod);
    expect($mailerMethod->replyTo)->equals([
      'reply_to_name' => 'Sender',
      'reply_to_email' => 'sender@email.com',
      'reply_to_name_email' => 'Sender <sender@email.com>',
    ]);
  }

  public function testItIgnoresInvalidBounceAddressAndUsesSenderAddressInstead() {
    $this->settings->set('bounce.address', 'invalid');
    $mailer = $this->factory->getDefaultMailer();
    $mailerMethod = $mailer->mailerMethod;
    $this->assertInstanceOf(PHPMail::class, $mailerMethod);
    expect($mailerMethod->returnPath)->equals('sender@email.com');
  }

  public function testItUsesSenderAddressInReplyToInCaseReplyToHasOnlyName() {
    $this->settings->set('reply_to', ['name' => 'Reply To']);
    $mailer = $this->factory->getDefaultMailer();
    $mailerMethod = $mailer->mailerMethod;
    $this->assertInstanceOf(PHPMail::class, $mailerMethod);
    expect($mailerMethod->replyTo)->equals([
      'reply_to_name' => 'Reply To',
      'reply_to_email' => 'sender@email.com',
      'reply_to_name_email' => 'Reply To <sender@email.com>',
    ]);
  }

  public function testItCanConvertNonASCIIEmailAddressString() {
    $this->settings->set('reply_to', [
      'name' => 'Reply-To Außergewöhnlichen тест системы',
      'address' => 'staff@mailpoet.com',
    ]);

    $this->settings->set('sender', [
      'name' => 'Sender Außergewöhnlichen тест системы',
      'address' => 'staff@mailpoet.com',
    ]);
    $mailer = $this->factory->getDefaultMailer();
    $mailerMethod = $mailer->mailerMethod;
    $this->assertInstanceOf(PHPMail::class, $mailerMethod);
    expect($mailerMethod->sender['from_name'])->equals(sprintf('=?utf-8?B?%s?=', base64_encode('Sender Außergewöhnlichen тест системы')));
    expect($mailerMethod->replyTo['reply_to_name'])->equals(sprintf('=?utf-8?B?%s?=', base64_encode('Reply-To Außergewöhnlichen тест системы')));
  }

  public function testItCachesDefaultMailerInstance() {
    expect($this->factory->getDefaultMailer() === $this->factory->getDefaultMailer())->true();
  }
}
