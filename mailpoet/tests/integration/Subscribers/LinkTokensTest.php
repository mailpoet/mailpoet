<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;

class LinkTokensTest extends \MailPoetTest {

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->linkTokens = new LinkTokens($this->subscribersRepository);
  }

  public function testItGeneratesSubscriberToken() {
    $subscriber1 = $this->createSubscriber('demo1@fake.loc');
    $subscriber2 = $this->createSubscriber('demo2@fake.loc');
    $token1 = $this->linkTokens->getToken($subscriber1);
    $token2 = $this->linkTokens->getToken($subscriber2);
    expect(strlen($token1))->equals(6);
    expect(strlen($token2))->equals(6);
    expect($token1 != $token2)->equals(true);
  }

  public function testItGetsSubscriberToken() {
    $subscriber1 = $this->createSubscriber('demo1@fake.loc', 'already-existing-token');
    $subscriber2 = $this->createSubscriber('demo2@fake.loc');
    expect($this->linkTokens->getToken($subscriber1))->equals('already-existing-token');
    expect(strlen($this->linkTokens->getToken($subscriber2)))->equals(6);
  }

  public function testItVerifiesSubscriberToken() {
    $subscriber = $this->createSubscriber('demo@fake.loc');
    $token = $this->linkTokens->getToken($subscriber);
    expect($this->linkTokens->verifyToken($subscriber, $token))->true();
    expect($this->linkTokens->verifyToken($subscriber, 'faketoken'))->false();
  }

  private function createSubscriber(string $email, ?string $linkToken = null): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLinkToken($linkToken);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    return $subscriber;
  }
}
