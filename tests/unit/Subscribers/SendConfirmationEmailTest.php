<?php

namespace MailPoet\Subscribers;

use AspectMock\Test as Mock;
use Codeception\Stub;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

class SendConfirmationEmailTest extends \MailPoetTest {

  function testItSendsConfirmationEmail() {
    Mock::double('MailPoet\Subscription\Url', [
      'getConfirmationUrl' => 'http://example.com'
    ]);
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com'
    ]);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function($email) {
          expect($email['body']['html'])->contains('<strong>Test segment</strong>');
          expect($email['body']['html'])->contains('<a target="_blank" href="http://example.com">Click here to confirm your subscription.</a>');
        }),
    ], $this);

    $sender = new SendConfirmationEmail($mailer);


    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Test segment'
      )
    );
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      array($segment->id)
    );

    $sender->sendConfirmationEmail($subscriber);
  }

  function testItSetsErrorsWhenConfirmationEmailCannotBeSent() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com'
    ]);

    $mailer = Stub::makeEmpty(Mailer::class, [
      'send' =>
        Stub\Expected::once(function () {
          throw new \Exception('send error');
        }),
    ], $this);

    $sender = new SendConfirmationEmail($mailer);

    $sender->sendConfirmationEmail($subscriber);
    // error is set on the subscriber model object
    expect($subscriber->getErrors()[0])->equals('send error');
  }

}
