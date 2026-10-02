<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class LinkTokensTest extends \MailPoetTest {
  private const UPGRADED_TOKEN = 'abcdef-0123456789abcdef012345678';

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->delete(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    $this->linkTokens = new LinkTokens($this->subscribersRepository, $this->settings);
  }

  public function _after() {
    $this->settings->delete(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    parent::_after();
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

  public function testItVerifiesUpgradedTokenByItsObsoletePartDuringGracePeriod() {
    $this->settings->set(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, Carbon::now()->addDay()->getTimestamp());
    $subscriber = $this->createSubscriber('demo@fake.loc', self::UPGRADED_TOKEN);
    verify($this->linkTokens->verifyToken($subscriber, self::UPGRADED_TOKEN))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcdef'))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcdef0123456789abcdef0123456789'))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcde0'))->false();
  }

  public function testItVerifiesWholeUpgradedTokenAfterGracePeriod() {
    $this->settings->set(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, Carbon::now()->subDay()->getTimestamp());
    $subscriber = $this->createSubscriber('demo@fake.loc', self::UPGRADED_TOKEN);
    verify($this->linkTokens->verifyToken($subscriber, self::UPGRADED_TOKEN))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcdef'))->false();
  }

  public function testItVerifiesWholeUpgradedTokenWhenGracePeriodWasNotStarted() {
    $subscriber = $this->createSubscriber('demo@fake.loc', self::UPGRADED_TOKEN);
    verify($this->linkTokens->verifyToken($subscriber, self::UPGRADED_TOKEN))->true();
    verify($this->linkTokens->verifyToken($subscriber, 'abcdef'))->false();
  }

  public function testItVerifiesWholeRandomTokenDuringGracePeriod() {
    $this->settings->set(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, Carbon::now()->addDay()->getTimestamp());
    $subscriber = $this->createSubscriber('demo@fake.loc');
    $token = $this->linkTokens->getToken($subscriber);
    verify($this->linkTokens->verifyToken($subscriber, $token))->true();
    verify($this->linkTokens->verifyToken($subscriber, substr($token, 0, SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH)))->false();
  }

  public function testItStartsGracePeriodOnlyOnce() {
    $this->linkTokens->startObsoleteTokensGracePeriod();
    $acceptedUntil = (int)$this->settings->get(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING);
    verify($acceptedUntil)->greaterThan(Carbon::now()->addMonths(11)->getTimestamp());

    $this->settings->set(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, $acceptedUntil - 100);
    $this->linkTokens->startObsoleteTokensGracePeriod();
    verify((int)$this->settings->get(LinkTokens::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING))->equals($acceptedUntil - 100);
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
