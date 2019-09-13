<?php

namespace MailPoet\Subscribers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

use MailPoet\Settings\SettingsController;

class NewSubscriberNotificationMailerTest extends \MailPoetTest {

  /** @var Subscriber */
  private $subscriber;

  /** @var Segment[] */
  private $segments;

  /** @var SettingsController */
  private $settings;

  function _before() {
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'subscriber@example.com';
    $this->segments = [Segment::create(), Segment::create()];
    $this->segments[0]->name = 'List1';
    $this->segments[1]->name = 'List2';
    $this->settings = new SettingsController();
  }

  function testItDoesNotSendIfNoSettings() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, null);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $service = new NewSubscriberNotificationMailer($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function testItDoesNotSendIfSettingsDoesNotHaveEnabled() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, []);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $service = new NewSubscriberNotificationMailer($mailer);
    $service->send($this->subscriber, $this->segments);
  }


  function testItDoesNotSendIfSettingsDoesNotHaveAddress() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => false]);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $service = new NewSubscriberNotificationMailer($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function testItDoesNotSendIfDisabled() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => false, 'address' => 'a@b.c']);
    $mailer = Stub::makeEmpty(Mailer::class, ['send' => Expected::never()], $this);
    $service = new NewSubscriberNotificationMailer($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function testItSends() {
    $this->settings->set(NewSubscriberNotificationMailer::SETTINGS_KEY, ['enabled' => true, 'address' => 'a@b.c']);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Expected::once(function($newsletter, $subscriber, $extra_params) {
          expect($subscriber)->equals('a@b.c');
          expect($newsletter)->hasKey('subject');
          expect($newsletter)->hasKey('body');
          expect($newsletter)->count(2);
          expect($newsletter['subject'])->equals('New subscriber to List1, List2');
          expect($newsletter['body'])->hasKey('html');
          expect($newsletter['body'])->hasKey('text');
          expect($newsletter['body'])->count(2);
          expect($newsletter['body']['text'])->contains('subscriber@example.com');
          expect($newsletter['body']['html'])->contains('subscriber@example.com');
          expect($extra_params['meta'])->equals([
            'email_type' => 'new_subscriber_notification',
            'subscriber_status' => 'unknown',
            'subscriber_source' => 'administrator',
          ]);
        }),
    ], $this);

    $service = new NewSubscriberNotificationMailer($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
