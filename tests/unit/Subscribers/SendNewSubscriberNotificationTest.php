<?php

namespace MailPoet\Subscribers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;

class SendNewSubscriberNotificationTest extends \MailPoetTest {

  /** @var Subscriber */
  private $subscriber;

  /** @var Segment[] */
  private $segments;

  function _before() {
    $this->subscriber = Subscriber::create();
    $this->subscriber->email = 'subscriber@example.com';
    $this->segments = [Segment::create(), Segment::create()];
    $this->segments[0]->name = 'List1';
    $this->segments[1]->name = 'List2';
  }

  function testItDoesNotSendIfNoSettings() {
    Setting::setValue(SendNewSubscriberNotification::SETTINGS_KEY, null);
    $mailer = Stub::makeEmpty(Mailer::class, ['getSenderNameAndAddress' => Expected::never()], $this);
    $service = new SendNewSubscriberNotification($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function testItDoesNotSendIfSettingsDoesNotHaveEnabled() {
    Setting::setValue(SendNewSubscriberNotification::SETTINGS_KEY, []);
    $mailer = Stub::makeEmpty(Mailer::class, ['getSenderNameAndAddress' => Expected::never()], $this);
    $service = new SendNewSubscriberNotification($mailer);
    $service->send($this->subscriber, $this->segments);
  }


  function testItDoesNotSendIfSettingsDoesNotHaveAddress() {
    Setting::setValue(SendNewSubscriberNotification::SETTINGS_KEY, ['enabled' => false]);
    $mailer = Stub::makeEmpty(Mailer::class, ['getSenderNameAndAddress' => Expected::never()], $this);
    $service = new SendNewSubscriberNotification($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function testItDoesNotSendIfDisabled() {
    Setting::setValue(SendNewSubscriberNotification::SETTINGS_KEY, ['enabled' => false, 'address' => 'a@b.c']);
    $mailer = Stub::makeEmpty(Mailer::class, ['getSenderNameAndAddress' => Expected::never()], $this);
    $service = new SendNewSubscriberNotification($mailer);
    $service->send($this->subscriber, $this->segments);
  }

  function testItSends() {
    Setting::setValue(SendNewSubscriberNotification::SETTINGS_KEY, ['enabled' => true, 'address' => 'a@b.c']);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'getSenderNameAndAddress' =>
        Expected::once(function($sender) {
          expect($sender)->count(2);
          expect($sender['address'])->startsWith('wordpress@');
          expect($sender['name'])->startsWith('wordpress@');
        }),
      'send' =>
        Expected::once(function($newsletter, $subscriber) {
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
        }),
    ], $this);

    $service = new SendNewSubscriberNotification($mailer);
    $service->send($this->subscriber, $this->segments);
  }

}
