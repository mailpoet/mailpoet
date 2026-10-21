<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use MailPoet\Captcha\CaptchaSession;
use MailPoet\Router\Endpoints\Captcha;

class CaptchaTest extends \MailPoetTest {
  private Captcha $endpoint;
  private CaptchaSession $session;

  public function _before() {
    parent::_before();
    $this->endpoint = $this->diContainer->get(Captcha::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function testRefreshIgnoresSessionIdsThatAreNotStrings(): void {
    $this->endpoint->refresh(['captcha_session_id' => ['a']]);
    $this->endpoint->refresh(['captcha_session_id' => 123]);
    $this->endpoint->refresh(['captcha_session_id' => '']);
    verify(true)->true();
  }

  public function testRefreshCreatesNothingForUnknownSession(): void {
    $sessionId = $this->session->generateSessionId();
    $this->endpoint->refresh(['captcha_session_id' => $sessionId]);
    verify($this->session->exists($sessionId))->false();
  }

  public function testRefreshReplacesThePhraseOfAnExistingSession(): void {
    $sessionId = $this->session->generateSessionId();
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'abc']);
    $this->endpoint->refresh(['captcha_session_id' => $sessionId]);
    verify($this->session->getCaptchaHash($sessionId)['phrase'])->notEquals('abc');
    $this->session->reset($sessionId);
  }
}
