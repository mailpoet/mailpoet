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
    verify(strlen($token1))->equals(6);
    verify(strlen($token2))->equals(6);
    verify($token1 != $token2)->equals(true);
  }

  public function testItGetsSubscriberToken() {
    $subscriber1 = $this->createSubscriber('demo1@fake.loc', 'already-existing-token');
    $subscriber2 = $this->createSubscriber('demo2@fake.loc');
    verify($this->linkTokens->getToken($subscriber1))->equals('already-existing-token');
    verify(strlen($this->linkTokens->getToken($subscriber2)))->equals(6);
  }

  public function testItVerifiesSubscriberToken() {
    $subscriber = $this->createSubscriber('demo@fake.loc');
    $token = $this->linkTokens->getToken($subscriber);
    verify($this->linkTokens->verifyToken($subscriber, $token))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'faketoken'))->false();
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

  /**
   * Regression for STOMAIL-8000 wave 6 review: generateToken() must return
   * null instead of producing an empty token for missing/empty emails;
   * persisting an empty linkToken would otherwise degrade verifyToken() to
   * the any-input-accepts state covered by the previous test. The method is
   * private, so we exercise it via reflection to avoid the email-NotBlank
   * validator that prevents reaching the missing-email path through getToken().
   */
  public function testGenerateTokenReturnsNullForMissingEmail() {
    $reflection = new \ReflectionMethod($this->linkTokens, 'generateToken');
    $reflection->setAccessible(true);

    verify($reflection->invoke($this->linkTokens, null))->equals(null);
    verify($reflection->invoke($this->linkTokens, ''))->equals(null);

    // Sanity: a real email still produces a non-empty 6-char token.
    $token = $reflection->invoke($this->linkTokens, 'demo@fake.loc');
    $this->assertIsString($token);
    verify(strlen($token))->equals(6);
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
