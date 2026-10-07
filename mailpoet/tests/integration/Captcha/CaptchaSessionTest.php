<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaSessionLimitException;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSessionTest extends \MailPoetTest {
  const SESSION_ID = 'ABCD1234ABCD1234ABCD1234ABCD1234';

  private CaptchaSession $captchaSession;
  private WPFunctions $wp;

  /** @var callable[] */
  private array $limitFilters = [];

  public function _before() {
    $this->wp = new WPFunctions;
    $this->captchaSession = new CaptchaSession($this->wp);
    $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
    $this->deleteBudgetTransients();
  }

  public function _after() {
    foreach ($this->limitFilters as $filter) {
      $this->wp->removeFilter('mailpoet_captcha_session_limit', $filter);
    }
    $this->limitFilters = [];
    $this->captchaSession->reset(self::SESSION_ID);
    $this->deleteBudgetTransients();
    unset($_SERVER['REMOTE_ADDR']);
    parent::_after();
  }

  public function testItCanStoreAndRetrieveFormData() {
    $formData = ['email' => 'email@example.com'];
    $this->captchaSession->setFormData(self::SESSION_ID, $formData);
    verify($this->captchaSession->getFormData(self::SESSION_ID))->equals($formData);
  }

  public function testItCanStoreAndRetrieveCaptchaHash() {
    $hash = '1234';
    $this->captchaSession->setCaptchaHash(self::SESSION_ID, $hash);
    verify($this->captchaSession->getCaptchaHash(self::SESSION_ID))->equals($hash);
  }

  public function testItCanResetSessionData() {
    $this->captchaSession->setFormData(self::SESSION_ID, ['email' => 'email@example.com']);
    $this->captchaSession->setCaptchaHash(self::SESSION_ID, 'hash123');
    $this->captchaSession->reset(self::SESSION_ID);
    verify($this->captchaSession->getFormData(self::SESSION_ID))->false();
    verify($this->captchaSession->getCaptchaHash(self::SESSION_ID))->false();
  }

  public function testDeletingTheCaptchaHashReportsWhetherThisCallRemovedIt() {
    $this->captchaSession->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    verify($this->captchaSession->deleteCaptchaHash(self::SESSION_ID))->true();
    verify($this->captchaSession->deleteCaptchaHash(self::SESSION_ID))->false();
    verify($this->captchaSession->deleteCaptchaHash('ABCD'))->false();
  }

  public function testItAcceptsOnlyWellFormedSessionIds() {
    verify($this->captchaSession->isValidId(self::SESSION_ID))->true();
    verify($this->captchaSession->isValidId($this->captchaSession->generateSessionId()))->true();
    foreach (['', 'ABCD', str_repeat('a', 31), str_repeat('a', 33), str_repeat('a', 31) . '-', str_repeat('a', 31) . ' ', null, 123, ['a']] as $id) {
      verify($this->captchaSession->isValidId($id))->false();
    }
  }

  public function testItDoesNotStoreDataForMalformedSessionIds() {
    $malformedIds = ['ABCD', str_repeat('a', 33), str_repeat('a', 31) . '_', str_repeat('a', 31) . '%'];
    foreach ($malformedIds as $id) {
      $this->captchaSession->setFormData($id, ['email' => 'email@example.com']);
      $this->captchaSession->setCaptchaHash($id, ['phrase' => 'abc']);
      verify(get_transient("MAILPOET_{$id}_form"))->false();
      verify(get_transient("MAILPOET_{$id}_hash"))->false();
      verify($this->captchaSession->getFormData($id))->false();
      verify($this->captchaSession->getCaptchaHash($id))->false();
      verify($this->captchaSession->exists($id))->false();
    }
    verify($this->countBudgetTransients())->equals(0);
  }

  public function testItIgnoresResetForMalformedSessionIds() {
    $this->captchaSession->setFormData(self::SESSION_ID, ['email' => 'email@example.com']);
    $this->captchaSession->reset('ABCD');
    verify($this->captchaSession->exists(self::SESSION_ID))->true();
  }

  public function testItReportsWhetherASessionExists() {
    verify($this->captchaSession->exists(self::SESSION_ID))->false();
    $this->captchaSession->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    verify($this->captchaSession->exists(self::SESSION_ID))->true();
    $this->captchaSession->reset(self::SESSION_ID);
    verify($this->captchaSession->exists(self::SESSION_ID))->false();
    $this->captchaSession->setFormData(self::SESSION_ID, ['email' => 'email@example.com']);
    verify($this->captchaSession->exists(self::SESSION_ID))->true();
  }

  public function testItLimitsNewSessionsPerSource() {
    for ($i = 0; $i < CaptchaSession::NEW_SESSION_LIMIT; $i++) {
      $this->captchaSession->registerNewSession();
    }
    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->registerNewSession();
  }

  public function testTheLimitErrorHasTheVisitorMessage() {
    $this->setLimit(0);
    try {
      $this->captchaSession->registerNewSession();
      $this->fail('Expected the session limit to be reached.');
    } catch (CaptchaSessionLimitException $e) {
      verify($e->getMessage())->equals('Too many CAPTCHA requests from your network. Please wait a few minutes and try again.');
      verify($e->getMeta()['error'])->equals($e->getMessage());
    }
  }

  public function testStoringSessionDataConsumesNoBudget() {
    $this->setLimit(1);
    for ($i = 0; $i < 3; $i++) {
      $id = $this->captchaSession->generateSessionId();
      $this->captchaSession->setFormData($id, ['a' => 'b']);
      $this->captchaSession->setSubscriptionFormData($id, ['a' => 'b']);
      $this->captchaSession->setCaptchaHash($id, ['phrase' => 'abc']);
      $this->captchaSession->reset($id);
    }
    verify($this->countBudgetTransients())->equals(0);
    $this->captchaSession->registerNewSession();
    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->registerNewSession();
  }

  public function testItSharesTheLimitInsideOneIpv6Network() {
    $this->setLimit(2);
    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:2::1';
    $this->captchaSession->registerNewSession();
    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:ffff:ffff:ffff:2';
    $this->captchaSession->registerNewSession();

    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:abcd::3';
    try {
      $this->captchaSession->registerNewSession();
      $this->fail('Expected the session limit to be reached.');
    } catch (CaptchaSessionLimitException $e) {
      // expected
    }

    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:3::1';
    $this->captchaSession->registerNewSession();
    verify($this->countBudgetTransients())->equals(2);
  }

  public function testItCountsIpv4AddressesSeparately() {
    $this->setLimit(1);
    $this->captchaSession->registerNewSession();
    $_SERVER['REMOTE_ADDR'] = '203.0.113.11';
    $this->captchaSession->registerNewSession();
    verify($this->countBudgetTransients())->equals(2);
  }

  public function testItCountsAnIpv4MappedIpv6AddressAsTheEmbeddedIpv4Address() {
    $this->setLimit(1);
    $_SERVER['REMOTE_ADDR'] = '::ffff:203.0.113.20';
    $this->captchaSession->registerNewSession();

    $_SERVER['REMOTE_ADDR'] = '::ffff:203.0.113.21';
    $this->captchaSession->registerNewSession();
    verify($this->countBudgetTransients())->equals(2);

    $_SERVER['REMOTE_ADDR'] = '203.0.113.20';
    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->registerNewSession();
  }

  public function testItDoesNotLimitWhenTheSourceIsUnknown() {
    $this->setLimit(1);
    unset($_SERVER['REMOTE_ADDR']);
    for ($i = 0; $i < 3; $i++) {
      $this->captchaSession->registerNewSession();
    }
    verify($this->countBudgetTransients())->equals(0);
  }

  public function testTheLimitCanBeRaisedWithAFilter() {
    $this->setLimit(3);
    for ($i = 0; $i < 3; $i++) {
      $this->captchaSession->registerNewSession();
    }
    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->registerNewSession();
  }

  public function testTheWindowDoesNotSlideWhenSessionsAreCounted() {
    $this->setLimit(5);
    $key = $this->getBudgetKey('203.0.113.10');
    $expires = time() + 100;
    set_transient($key, ['count' => 1, 'expires' => $expires], 100);

    $this->captchaSession->registerNewSession();
    $this->captchaSession->registerNewSession();

    $stored = $this->getBudgetWindow($key);
    verify($stored['count'])->equals(3);
    verify($stored['expires'])->equals($expires);
    $timeout = get_option('_transient_timeout_' . $key);
    $this->assertIsNumeric($timeout);
    $this->assertLessThanOrEqual(100, (int)$timeout - time());
  }

  public function testTheCountStartsAgainWhenTheWindowHasEnded() {
    $this->setLimit(2);
    $key = $this->getBudgetKey('203.0.113.10');
    $this->captchaSession->registerNewSession();
    $this->captchaSession->registerNewSession();
    try {
      $this->captchaSession->registerNewSession();
      $this->fail('Expected the session limit to be reached.');
    } catch (CaptchaSessionLimitException $e) {
      // expected
    }

    set_transient($key, ['count' => 2, 'expires' => time() - 1], CaptchaSession::NEW_SESSION_WINDOW);
    $this->captchaSession->registerNewSession();

    $stored = $this->getBudgetWindow($key);
    verify($stored['count'])->equals(1);
    $this->assertGreaterThan(time() + CaptchaSession::NEW_SESSION_WINDOW - 5, $stored['expires']);
  }

  public function testItStartsANewWindowWhenTheStoredCountHasNoExpiry() {
    $this->setLimit(1);
    $key = $this->getBudgetKey('203.0.113.10');
    set_transient($key, 5, CaptchaSession::NEW_SESSION_WINDOW);
    $this->captchaSession->registerNewSession();
    verify($this->getBudgetWindow($key)['count'])->equals(1);
  }

  private function setLimit(int $limit): void {
    $filter = function () use ($limit) {
      return $limit;
    };
    $this->wp->addFilter('mailpoet_captcha_session_limit', $filter);
    $this->limitFilters[] = $filter;
  }

  private function getBudgetWindow(string $key): array {
    $stored = get_transient($key);
    $this->assertIsArray($stored);
    return $stored;
  }

  private function getBudgetKey(string $ip): string {
    return 'MAILPOET_captcha_sessions_' . md5((string)inet_pton($ip));
  }

  private function countBudgetTransients(): int {
    global $wpdb;
    return (int)$wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_MAILPOET\\_captcha\\_sessions\\_%'"
    );
  }

  private function deleteBudgetTransients(): void {
    global $wpdb;
    $wpdb->query(
      "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_MAILPOET\\_captcha\\_sessions\\_%' OR option_name LIKE '\\_transient\\_timeout\\_MAILPOET\\_captcha\\_sessions\\_%'"
    );
    wp_cache_flush();
  }
}
