<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;

class SourceTest extends \MailPoetTest {
  public function testItDoesntOverrideSource() {
    $subscriber = Subscriber::createOrUpdate([
      'source' => Source::FORM,
    ]);
    $updatedSubscriber = Source::setSource($subscriber, Source::API);
    expect($updatedSubscriber->source)->equals(Source::FORM);
  }

  public function testItDoesntAllowInvalidSource() {
    $subscriber = Subscriber::createOrUpdate([
      'source' => Source::UNKNOWN,
    ]);
    $this->expectException('\InvalidArgumentException');
    Source::setSource($subscriber, 'invalid source');
  }

  public function testItWorksWhenNoSourceIsSet() {
    $subscriber = Subscriber::createOrUpdate([]);
    $updatedSubscriber = Source::setSource($subscriber, Source::FORM);
    expect($updatedSubscriber->source)->equals(Source::FORM);
  }

  public function testItWorks() {
    $subscriber = Subscriber::createOrUpdate([
      'source' => Source::UNKNOWN,
    ]);
    $updatedSubscriber = Source::setSource($subscriber, Source::FORM);
    expect($updatedSubscriber->source)->equals(Source::FORM);
  }
}
