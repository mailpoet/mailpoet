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
    $ids = [];
    for ($i = 0; $i < CaptchaSession::NEW_SESSION_LIMIT; $i++) {
      $ids[] = $this->captchaSession->generateSessionId();
      $this->captchaSession->setCaptchaHash(end($ids), ['phrase' => 'abc']);
    }

    $extraId = $this->captchaSession->generateSessionId();
    try {
      $this->captchaSession->setCaptchaHash($extraId, ['phrase' => 'abc']);
      $this->fail('Expected the session limit to be reached.');
    } catch (CaptchaSessionLimitException $e) {
      verify($this->captchaSession->exists($extraId))->false();
    }

    foreach ($ids as $id) {
      $this->captchaSession->reset($id);
    }
  }

  public function testItAppliesTheLimitToFormDataToo() {
    $this->setLimit(1);
    $this->captchaSession->setFormData($this->captchaSession->generateSessionId(), ['a' => 'b']);
    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->setFormData($this->captchaSession->generateSessionId(), ['a' => 'b']);
  }

  public function testItSharesTheLimitInsideOneIpv6Network() {
    $this->setLimit(2);
    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:2::1';
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:ffff:ffff:ffff:2';
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);

    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:abcd::3';
    try {
      $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
      $this->fail('Expected the session limit to be reached.');
    } catch (CaptchaSessionLimitException $e) {
      // expected
    }

    $_SERVER['REMOTE_ADDR'] = '2001:db8:1:3::1';
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
    verify(true)->true();
  }

  public function testItCountsIpv4AddressesSeparately() {
    $this->setLimit(1);
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
    $_SERVER['REMOTE_ADDR'] = '203.0.113.11';
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
    verify($this->countBudgetTransients())->equals(2);
  }

  public function testItDoesNotLimitWhenTheSourceIsUnknown() {
    $this->setLimit(1);
    unset($_SERVER['REMOTE_ADDR']);
    for ($i = 0; $i < 3; $i++) {
      $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
    }
    verify($this->countBudgetTransients())->equals(0);
  }

  public function testExistingSessionsConsumeNoBudget() {
    $this->setLimit(1);
    $this->captchaSession->setCaptchaHash(self::SESSION_ID, ['phrase' => 'abc']);
    $this->captchaSession->setCaptchaHash(self::SESSION_ID, ['phrase' => 'def']);
    $this->captchaSession->setFormData(self::SESSION_ID, ['email' => 'email@example.com']);
    verify($this->captchaSession->getCaptchaHash(self::SESSION_ID))->equals(['phrase' => 'def']);

    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
  }

  public function testTheLimitCanBeRaisedWithAFilter() {
    $this->setLimit(3);
    for ($i = 0; $i < 3; $i++) {
      $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
    }
    $this->expectException(CaptchaSessionLimitException::class);
    $this->captchaSession->setCaptchaHash($this->captchaSession->generateSessionId(), ['phrase' => 'abc']);
  }

  private function setLimit(int $limit): void {
    $filter = function () use ($limit) {
      return $limit;
    };
    $this->wp->addFilter('mailpoet_captcha_session_limit', $filter);
    $this->limitFilters[] = $filter;
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
