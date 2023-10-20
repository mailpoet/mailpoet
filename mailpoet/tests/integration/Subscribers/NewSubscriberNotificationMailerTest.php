<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Config\Renderer;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class NewSubscriberNotificationMailerTest extends \MailPoetTest {

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SegmentEntity[] */
  private $segments = [];

  /** @var SettingsController */
  private $settings;

  public function _before() {
    $this->subscriber = (new SubscriberFactory())
      ->withEmail('subscriber@example.com')
      ->create();
    $this->segments[] = (new SegmentFactory())->withName('List1')->create();
    $this->segments[] = (new SegmentFactory())->withName('List2')->create();
    $this->settings = SettingsController::getInstance();
  }

  public function testItDoesNotSendIfNoSettings() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, null);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $service = new NewSubscriberNotificationMailer($mailerFactory, $this->diContainer->get(Renderer::class), $this->diContainer->get(SettingsController::class));
    $service->send($this->subscriber, $this->segments);
  }

  public function testItDoesNotSendIfSettingsDoesNotHaveEnabled() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, []);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $service = new NewSubscriberNotificationMailer($mailerFactory, $this->diContainer->get(Renderer::class), $this->diContainer->get(SettingsController::class));
    $service->send($this->subscriber, $this->segments);
  }

  public function testItDoesNotSendIfSettingsDoesNotHaveAddress() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => false]);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $service = new NewSubscriberNotificationMailer($mailerFactory, $this->diContainer->get(Renderer::class), $this->diContainer->get(SettingsController::class));
    $service->send($this->subscriber, $this->segments);
  }

  public function testItDoesNotSendIfDisabled() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => false, 'address' => 'a@b.c']);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $service = new NewSubscriberNotificationMailer($mailerFactory, $this->diContainer->get(Renderer::class), $this->diContainer->get(SettingsController::class));
    $service->send($this->subscriber, $this->segments);
  }

  public function testItSends() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => true, 'address' => 'a@b.c']);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Expected::once(function($newsletter, $subscriber, $extraParams) {
          verify($subscriber)->equals('a@b.c');
          verify($newsletter)->arrayHasKey('subject');
          verify($newsletter)->arrayHasKey('body');
          verify($newsletter)->arrayCount(2);
          verify($newsletter['subject'])->equals('New subscriber to List1, List2');
          verify($newsletter['body'])->arrayHasKey('html');
          verify($newsletter['body'])->arrayHasKey('text');
          verify($newsletter['body'])->arrayCount(2);
          verify($newsletter['body']['text'])->stringContainsString('subscriber@example.com');
          verify($newsletter['body']['html'])->stringContainsString('subscriber@example.com');
          verify($extraParams['meta'])->equals([
            'email_type' => 'new_subscriber_notification',
            'subscriber_status' => 'unknown',
            'subscriber_source' => 'administrator',
          ]);
        }),
    ], $this);

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);
    $service = new NewSubscriberNotificationMailer($mailerFactory, $this->diContainer->get(Renderer::class), $this->diContainer->get(SettingsController::class));
    $service->send($this->subscriber, $this->segments);
  }

  public function testItSendsWithSubscriberEntity() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => true, 'address' => 'a@b.c']);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Expected::once(function($newsletter, $subscriber, $extraParams) {
          verify($subscriber)->equals('a@b.c');
          verify($newsletter)->arrayHasKey('subject');
          verify($newsletter)->arrayHasKey('body');
          verify($newsletter)->arrayCount(2);
          verify($newsletter['subject'])->stringMatchesFormat('New subscriber to List %s, List %s');
          verify($newsletter['body'])->arrayHasKey('html');
          verify($newsletter['body'])->arrayHasKey('text');
          verify($newsletter['body'])->arrayCount(2);
          verify($newsletter['body']['text'])->stringContainsString('subscriber@example.com');
          verify($newsletter['body']['html'])->stringContainsString('subscriber@example.com');
          verify($extraParams['meta'])->equals([
            'email_type' => 'new_subscriber_notification',
            'subscriber_status' => 'unknown',
            'subscriber_source' => 'administrator',
          ]);
        }),
    ], $this);

    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriberEntity = $subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberEntity);

    $segments = [(new SegmentFactory())->create(), (new SegmentFactory())->create()];

    $mailerFactory = $this->createMock(MailerFactory::class);
    $mailerFactory->method('getDefaultMailer')->willReturn($mailer);

    $service = new NewSubscriberNotificationMailer($mailerFactory, $this->diContainer->get(Renderer::class), $this->diContainer->get(SettingsController::class));
    $service->send($subscriberEntity, $segments);
  }
}
