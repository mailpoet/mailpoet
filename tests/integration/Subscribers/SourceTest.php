<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;

class SourceTest extends \MailPoetTest {

  public function testItDoesntOverrideSource() {
    $subscriber = Subscriber::createOrUpdate([
      'source' => Source::FORM,
    ]);
    $updated_subscriber = Source::setSource($subscriber, Source::API);
    expect($updated_subscriber->source)->equals(Source::FORM);
  }

  public function testItDoesntAllowInvalidSource() {
    $subscriber = Subscriber::createOrUpdate([
      'source' => Source::UNKNOWN,
    ]);
    $this->setExpectedException('\InvalidArgumentException');
    Source::setSource($subscriber, 'invalid source');
  }

  public function testItWorksWhenNoSourceIsSet() {
    $subscriber = Subscriber::createOrUpdate([]);
    $updated_subscriber = Source::setSource($subscriber, Source::FORM);
    expect($updated_subscriber->source)->equals(Source::FORM);
  }

  public function testItWorks() {
    $subscriber = Subscriber::createOrUpdate([
      'source' => Source::UNKNOWN,
    ]);
    $updated_subscriber = Source::setSource($subscriber, Source::FORM);
    expect($updated_subscriber->source)->equals(Source::FORM);
  }

}
