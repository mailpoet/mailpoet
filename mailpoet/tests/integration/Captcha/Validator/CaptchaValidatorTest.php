<?php declare(strict_types = 1);

namespace Mailpoet\Test\Captcha\Validator;

use MailPoet\Captcha\CaptchaPhrase;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Config\Populator;
use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class CaptchaValidatorTest extends \MailPoetTest {
  private CaptchaValidator $testee;
  private CaptchaSession $session;

  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';

  /** @var string[] */
  private array $createdSessionIds = [];

  public function _before() {
    $this->testee = $this->diContainer->get(CaptchaValidator::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function _after() {
    $this->session->reset(self::SESSION_ID);
    foreach ($this->createdSessionIds as $sessionId) {
      $this->session->reset($sessionId);
    }
    parent::_after();
  }

  private function countHashTransients(): int {
    global $wpdb;
    return (int)$wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_MAILPOET\\_%\\_hash'"
    );
  }

  private function getValidationErrorMeta(array $data, bool $existingChallenge = false): array {
    try {
      if ($existingChallenge) {
        $this->testee->validateExistingChallenge($data);
      } else {
        $this->testee->validate($data);
      }
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      if (isset($meta['captcha_session_id']) && is_string($meta['captcha_session_id'])) {
        $this->createdSessionIds[] = $meta['captcha_session_id'];
      }
      return $meta;
    }
    $this->fail('Expected a ValidationError.');
  }

  public function testMissingCaptchaSessionIdStartsANewChallenge() {
    $meta = $this->getValidationErrorMeta(['captcha' => 'abc']);
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertTrue($meta['show_captcha']);
    $this->assertTrue($this->session->isValidId($meta['captcha_session_id']));
    $this->assertTrue($this->session->exists($meta['captcha_session_id']));
    $this->assertNotEmpty($this->session->getCaptchaHash($meta['captcha_session_id']));
  }

  public function testMalformedCaptchaSessionIdStartsANewChallenge() {
    foreach (['123', ['a'], 12345] as $malformedId) {
      $meta = $this->getValidationErrorMeta(['captcha' => 'abc', 'captcha_session_id' => $malformedId]);
      $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
      $this->assertTrue($this->session->isValidId($meta['captcha_session_id']));
    }
    $this->assertFalse(get_transient('MAILPOET_123_hash'));
  }

  public function testEmptyCaptchaForUnknownSessionStartsANewChallenge() {
    $this->diContainer->get(Populator::class)->up();

    $meta = $this->getValidationErrorMeta(['captcha_session_id' => self::SESSION_ID]);
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertTrue(array_key_exists('redirect_url', $meta));
    $this->assertNotEquals(self::SESSION_ID, $meta['captcha_session_id']);
    $this->assertFalse($this->session->exists(self::SESSION_ID));
  }

  public function testItDropsRegisterOnlyKeysFromTheStashWhenStartingANewChallenge() {
    $meta = $this->getValidationErrorMeta([
      'email' => 'subscriber@example.com',
      'form_id' => 7,
      'referrer_form' => 'wp',
      'referrer_form_url' => 'https://evil.example/register',
      'rendered' => true,
      'action_url' => 'https://evil.example/register',
    ]);
    $stash = $this->session->getFormData($meta['captcha_session_id']);
    $this->assertSame(['email' => 'subscriber@example.com', 'form_id' => 7], $stash);
  }

  public function testItKeepsTheStashWithoutStaleSessionDataWhenStartingANewChallenge() {
    $meta = $this->getValidationErrorMeta([
      'captcha_session_id' => self::SESSION_ID,
      'email' => 'subscriber@example.com',
      'form_id' => 7,
    ]);
    $this->assertSame(
      ['email' => 'subscriber@example.com', 'form_id' => 7],
      $this->session->getFormData($meta['captcha_session_id'])
    );
  }

  public function testEmptyCaptchaForExistingSessionRefreshesItsPhrase() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);

    $meta = $this->getValidationErrorMeta(['captcha_session_id' => self::SESSION_ID]);
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertEquals(self::SESSION_ID, $meta['captcha_session_id']);
    $this->assertNotEquals('abc', $this->session->getCaptchaHash(self::SESSION_ID)['phrase']);
  }

  public function testWrongCaptchaThrowsError() {
    $this->diContainer->get(Populator::class)->up();

    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $meta = $this->getValidationErrorMeta(['captcha' => 'xyz', 'captcha_session_id' => self::SESSION_ID]);
    $this->assertEquals('The characters entered do not match with the previous CAPTCHA.', $meta['error']);
    $this->assertTrue(array_key_exists('redirect_url', $meta));
  }

  public function testWrongAnswersAreCountedWithoutChangingTheSessionId() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $data = ['captcha' => 'not-a-phrase', 'captcha_session_id' => self::SESSION_ID];

    $this->getValidationErrorMeta($data);
    $this->assertSame(1, $this->session->getCaptchaHash(self::SESSION_ID)['attempts']);
    $this->getValidationErrorMeta($data);
    $this->getValidationErrorMeta($data);
    $this->assertSame(3, $this->session->getCaptchaHash(self::SESSION_ID)['attempts']);
    $this->assertNotEquals('abc', $this->session->getCaptchaHash(self::SESSION_ID)['phrase']);
  }

  public function testTheTenthWrongAnswerReplacesTheSessionWithANewChallenge() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $this->session->setFormData(self::SESSION_ID, ['email' => 'a@example.com']);
    $data = ['captcha' => 'not-a-phrase', 'captcha_session_id' => self::SESSION_ID];

    for ($i = 1; $i < CaptchaPhrase::MAX_ATTEMPTS; $i++) {
      $meta = $this->getValidationErrorMeta($data);
      $this->assertEquals('The characters entered do not match with the previous CAPTCHA.', $meta['error']);
      $this->assertTrue($meta['refresh_captcha']);
    }

    $meta = $this->getValidationErrorMeta($data + ['email' => 'a@example.com']);
    $this->assertEquals('Too many incorrect attempts. Here’s a new CAPTCHA to try.', $meta['error']);
    $this->assertTrue($meta['show_captcha']);
    $this->assertTrue(array_key_exists('redirect_url', $meta));
    $this->assertNotEquals(self::SESSION_ID, $meta['captcha_session_id']);
    $this->assertFalse($this->session->exists(self::SESSION_ID));
    $this->assertSame(0, $this->session->getCaptchaHash($meta['captcha_session_id'])['attempts']);
    $this->assertSame(['email' => 'a@example.com'], $this->session->getFormData($meta['captcha_session_id']));

    $meta = $this->getValidationErrorMeta($data);
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertNotEquals(self::SESSION_ID, $meta['captcha_session_id']);
  }

  public function testStoredPhrasesWithoutAnAttemptCountStartAtZero() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $data = ['captcha' => 'not-a-phrase', 'captcha_session_id' => self::SESSION_ID];

    for ($i = 1; $i < CaptchaPhrase::MAX_ATTEMPTS; $i++) {
      $meta = $this->getValidationErrorMeta($data);
      $this->assertEquals('The characters entered do not match with the previous CAPTCHA.', $meta['error']);
    }
    $this->assertTrue($this->session->exists(self::SESSION_ID));
  }

  public function testItStartsANewChallengeWhenThePhraseIsGone() {
    $this->diContainer->get(Populator::class)->up();

    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => null]);
    $meta = $this->getValidationErrorMeta(['captcha' => 'xyz', 'captcha_session_id' => self::SESSION_ID]);
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertNotEquals(self::SESSION_ID, $meta['captcha_session_id']);
    $this->assertTrue(array_key_exists('redirect_url', $meta));
  }

  public function testAnswerForUnknownSessionNeverVerifiesAgainstANewSession() {
    $meta = $this->getValidationErrorMeta(['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID]);
    $newPhrase = $this->session->getCaptchaHash($meta['captcha_session_id'])['phrase'];
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertFalse($this->session->exists(self::SESSION_ID));
    $this->assertNotEmpty($newPhrase);
  }

  public function testReturnsTrueWhenCaptchaIsSolved() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $this->assertTrue($this->testee->validate(['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID]));
  }

  public function testAnAcceptedAnswerWorksOnlyOnce() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $data = ['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID];

    $this->assertTrue($this->testee->validateChallenge($data));
    $this->assertFalse($this->session->getCaptchaHash(self::SESSION_ID));

    $meta = $this->getValidationErrorMeta($data);
    $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
    $this->assertNotEquals(self::SESSION_ID, $meta['captcha_session_id']);
  }

  public function testAnAcceptedAnswerKeepsTheFormStashOfTheSession() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $this->session->setFormData(self::SESSION_ID, ['email' => 'a@example.com']);

    $this->assertTrue($this->testee->validateChallenge(['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID]));

    $this->assertEquals(['email' => 'a@example.com'], $this->session->getFormData(self::SESSION_ID));
  }

  public function testAnAcceptedExistingChallengeAnswerWorksOnlyOnceAndDropsTheStash() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $this->session->setFormData(self::SESSION_ID, ['user_email' => 'a@example.com']);
    $data = ['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID];

    $this->assertTrue($this->testee->validateExistingChallenge($data));
    $this->assertFalse($this->session->exists(self::SESSION_ID));

    $meta = $this->getValidationErrorMeta($data, true);
    $this->assertEquals('CAPTCHA verification failed. Please try again.', $meta['error']);
  }

  public function testExistingChallengeNeverStartsANewSession() {
    $failures = [
      [],
      ['captcha' => 'abc'],
      ['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID],
      ['captcha' => 'abc', 'captcha_session_id' => '123'],
    ];
    $before = $this->countHashTransients();
    foreach ($failures as $data) {
      $this->assertEquals(
        'CAPTCHA verification failed. Please try again.',
        $this->getValidationErrorMeta($data, true)['error']
      );
    }
    $this->assertEquals($before, $this->countHashTransients());
  }

  public function testExistingChallengeResetsTheSessionOnAWrongAnswer() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'xyz']);
    $this->session->setFormData(self::SESSION_ID, ['user_email' => 'a@example.com']);

    $meta = $this->getValidationErrorMeta(['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID], true);
    $this->assertEquals('CAPTCHA verification failed. Please try again.', $meta['error']);
    $this->assertArrayNotHasKey('show_captcha', $meta);
    $this->assertFalse($this->session->exists(self::SESSION_ID));
  }

  public function testExistingChallengeAcceptsTheCorrectAnswer() {
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $this->assertTrue($this->testee->validateExistingChallenge(['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID]));
  }

  public function testExistingChallengeIsSkippedForExemptUsers() {
    $adminId = $this->tester->createWordPressUser('captcha-exempt@example.com', 'administrator');
    wp_set_current_user($adminId);
    try {
      $this->assertTrue($this->testee->validateExistingChallenge([]));
    } finally {
      wp_set_current_user(0);
    }
  }

  public function testItRefusesNewChallengesWhenTheSourceHasTooManySessions() {
    $wp = new WPFunctions;
    $filter = function () {
      return 1;
    };
    $_SERVER['REMOTE_ADDR'] = '203.0.113.77';
    $wp->addFilter('mailpoet_captcha_session_limit', $filter);
    try {
      $first = $this->getValidationErrorMeta([]);
      $this->assertEquals('Please fill in the CAPTCHA.', $first['error']);
      $second = $this->getValidationErrorMeta([]);
      $this->assertEquals('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.', $second['error']);
      $this->assertArrayNotHasKey('show_captcha', $second);
    } finally {
      $wp->removeFilter('mailpoet_captcha_session_limit', $filter);
      unset($_SERVER['REMOTE_ADDR']);
      global $wpdb;
      $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%MAILPOET\\_captcha\\_sessions\\_%'");
      wp_cache_flush();
    }
  }

  public function testAnAnswerThatAnotherRequestClaimedFirstIsRejected() {
    $testee = $this->makeValidatorWithConcurrentClaim();
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);

    $meta = $this->getValidationErrorMetaFrom($testee, ['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID]);

    $this->assertTrue($meta['show_captcha']);
  }

  public function testAnAnswerThatAnotherRequestClaimedFirstIsRejectedOnTheRegistrationPath() {
    $testee = $this->makeValidatorWithConcurrentClaim();
    $this->session->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);

    $meta = $this->getValidationErrorMetaFrom($testee, ['captcha' => 'abc', 'captcha_session_id' => self::SESSION_ID], true);

    $this->assertEquals('CAPTCHA verification failed. Please try again.', $meta['error']);
  }

  /**
   * A validator whose phrase lookup reports the stored phrase and then lets a parallel request take it.
   */
  private function makeValidatorWithConcurrentClaim(): CaptchaValidator {
    $session = $this->session;
    $phrase = new class($session) extends CaptchaPhrase {
      private CaptchaSession $captchaSession;

      public function __construct(
        CaptchaSession $captchaSession
      ) {
        parent::__construct($captchaSession);
        $this->captchaSession = $captchaSession;
      }

      public function getPhrase(string $sessionId): ?string {
        $stored = parent::getPhrase($sessionId);
        $this->captchaSession->deleteCaptchaHash($sessionId);
        return $stored;
      }
    };
    return new CaptchaValidator(
      $this->diContainer->get(CaptchaUrlFactory::class),
      $phrase,
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SubscriberIPsRepository::class),
      $this->diContainer->get(SubscribersRepository::class),
      $session
    );
  }

  private function getValidationErrorMetaFrom(CaptchaValidator $testee, array $data, bool $existingChallenge = false): array {
    try {
      if ($existingChallenge) {
        $testee->validateExistingChallenge($data);
      } else {
        $testee->validate($data);
      }
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      if (isset($meta['captcha_session_id']) && is_string($meta['captcha_session_id'])) {
        $this->createdSessionIds[] = $meta['captcha_session_id'];
      }
      return $meta;
    }
    $this->fail('Expected a ValidationError.');
  }

  public function testItRequiresCaptchaForFirstSubscription() {
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    verify($result)->equals(true);
  }

  public function testItRequiresCaptchaForUnrepeatedIPAddress() {
    $result = $this->testee->isRequired();
    verify($result)->equals(true);
  }

  public function testItTakesFilterIntoAccountToDisableCaptcha() {
    $wp = new WPFunctions;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $filter = function () {
      return 1;
    };
    $wp->addFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    verify($result)->equals(false);

    $result = $this->testee->isRequired();
    verify($result)->equals(false);

    $subscriberFactory = new SubscriberFactory();
    $subscriber = $subscriberFactory
      ->withCountConfirmations(1)
      ->create();

    $result = $this->testee->isRequired($subscriber->getEmail());
    verify($result)->equals(true);

    $ip = new SubscriberIPEntity('127.0.0.1');
    $ip->setCreatedAt(Carbon::now()->subMinutes(1));
    $this->entityManager->persist($ip);
    $this->entityManager->flush();
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    verify($result)->equals(true);

    unset($_SERVER['REMOTE_ADDR']);
    $wp->removeFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
  }
}
