<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaRenderer;
use MailPoet\Captcha\CaptchaSession;

class CaptchaRendererTest extends \MailPoetTest {
  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';

  private CaptchaRenderer $testee;
  private CaptchaSession $session;

  public function _before() {
    $this->testee = $this->diContainer->get(CaptchaRenderer::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function _after() {
    $this->session->reset(self::SESSION_ID);
    parent::_after();
  }

  public function testItRendersImage(): void {
    $sessionId = self::SESSION_ID;
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'a']);
    $this->testee->renderImage($sessionId);
    $this->assertStringContainsString('JPEG', $this->getActualOutputForAssertion());
  }

  public function testItRendersAudio(): void {
    $sessionId = self::SESSION_ID;
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'a7k2m']);
    $this->testee->renderAudio($sessionId);
    $audio = $this->getActualOutputForAssertion();
    verify(substr($audio, 0, 4))->equals('RIFF');
    verify(substr($audio, 8, 4))->equals('WAVE');
    verify(strlen($audio))->greaterThan(44);
    verify(in_array('Content-Type: audio/wav', xdebug_get_headers(), true))->true();
  }

  public function testItRendersDifferentAudioOnEveryRequest(): void {
    $sessionId = self::SESSION_ID;
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'a7k2m']);
    ob_start();
    $this->testee->renderAudio($sessionId);
    $first = (string)ob_get_clean();
    ob_start();
    $this->testee->renderAudio($sessionId);
    $second = (string)ob_get_clean();
    verify($first)->notEmpty();
    verify($first)->notEquals($second);
  }

  public function testItRefreshesPhrase(): void {
    $sessionId = self::SESSION_ID;
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'abc']);
    $this->testee->refreshPhrase($sessionId);
    $this->assertNotEquals('abc', $this->session->getCaptchaHash($sessionId)['phrase']);
  }

  public function testItDoesNotRefreshPhraseOfUnknownSession(): void {
    verify($this->testee->refreshPhrase(self::SESSION_ID))->equals('');
    verify($this->session->exists(self::SESSION_ID))->false();
    verify($this->session->getCaptchaHash(self::SESSION_ID))->false();
  }

  public function testItRendersNothingForUnknownSession(): void {
    $this->testee->renderImage(self::SESSION_ID);
    $this->testee->renderAudio(self::SESSION_ID);
    verify($this->getActualOutputForAssertion())->equals('');
    verify($this->session->exists(self::SESSION_ID))->false();
  }
}
