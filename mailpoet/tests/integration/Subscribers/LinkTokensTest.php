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
    verify(strlen($token1))->equals(SubscriberEntity::LINK_TOKEN_LENGTH);
    verify(strlen($token2))->equals(SubscriberEntity::LINK_TOKEN_LENGTH);
    verify($token1 != $token2)->equals(true);
  }

  public function testItDoesNotDeriveNewTokensFromEmail() {
    $subscriber = $this->createSubscriber('demo@fake.loc');
    $token = $this->linkTokens->getToken($subscriber);
    $emailHash = md5((defined('AUTH_KEY') ? AUTH_KEY : '') . 'demo@fake.loc');
    verify(substr($emailHash, 0, strlen($token)))->notEquals($token);
  }

  public function testItGetsSubscriberToken() {
    $subscriber1 = $this->createSubscriber('demo1@fake.loc', 'already-existing-token');
    $subscriber2 = $this->createSubscriber('demo2@fake.loc');
    verify($this->linkTokens->getToken($subscriber1))->equals('already-existing-token');
    verify(strlen($this->linkTokens->getToken($subscriber2)))->equals(SubscriberEntity::LINK_TOKEN_LENGTH);
  }

  public function testItVerifiesSubscriberToken() {
    $subscriber = $this->createSubscriber('demo@fake.loc');
    $token = $this->linkTokens->getToken($subscriber);
    verify($this->linkTokens->verifyToken($subscriber, $token))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'faketoken'))->false();
  }

  public function testItVerifiesObsoleteShortToken() {
    $subscriber = $this->createSubscriber('demo@fake.loc', 'abcdef');
    verify($this->linkTokens->verifyToken($subscriber, 'abcdef'))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcdef0123456789abcdef0123456789'))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcde0'))->false();
  }

  /**
   * Regression for STOMAIL-8000 wave 6 review: an empty stored linkToken used
   * to silently authenticate any input. hash_equals('', substr($x, 0, 0)) is
   * always true, so verifyToken() needs to fail closed when the database
   * token is empty. Force the empty state via the repository directly to
   * sidestep generateToken()'s upstream guard.
   */
  public function testItRejectsEmptyStoredTokenInsteadOfAcceptingAnyInput() {
    $subscriber = $this->createSubscriber('demo@fake.loc');
    $subscriber->setLinkToken('');
    $this->subscribersRepository->flush();

    verify($this->linkTokens->verifyToken($subscriber, ''))->false();
    verify($this->linkTokens->verifyToken($subscriber, 'whatever'))->false();
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
