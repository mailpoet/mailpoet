<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class LinkTokensTest extends \MailPoetTest {

  /** @var LinkTokens */
  private $link_tokens;

  public function _before() {
    parent::_before();
    $this->link_tokens = new LinkTokens;
  }

  public function testItGeneratesSubscriberToken() {
    $subscriber1 = $this->makeSubscriber(['email' => 'demo1@fake.loc']);
    $subscriber2 = $this->makeSubscriber(['email' => 'demo2@fake.loc']);
    $token1 = $this->link_tokens->getToken($subscriber1);
    $token2 = $this->link_tokens->getToken($subscriber2);
    expect(strlen($token1))->equals(6);
    expect(strlen($token2))->equals(6);
    expect($token1 != $token2)->equals(true);
  }

  public function testItGetsSubscriberToken() {
    $subscriber1 = $this->makeSubscriber(['email' => 'demo1@fake.loc', 'link_token' => 'already-existing-token']);
    $subscriber2 = $this->makeSubscriber(['email' => 'demo2@fake.loc']);
    expect($this->link_tokens->getToken($subscriber1))->equals('already-existing-token');
    expect(strlen($this->link_tokens->getToken($subscriber2)))->equals(6);
  }

  public function testItVerifiesSubscriberToken() {
    $subscriber = $this->makeSubscriber([
      'email' => 'demo@fake.loc',
    ]);
    $token = $this->link_tokens->getToken($subscriber);
    expect($this->link_tokens->verifyToken($subscriber, $token))->true();
    expect($this->link_tokens->verifyToken($subscriber, 'faketoken'))->false();
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }

  private function makeSubscriber($data) {
    $subscriber = Subscriber::create();
    $subscriber->hydrate($data);
    $subscriber->save();
    return $subscriber;
  }

}
