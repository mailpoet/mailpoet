<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription\Captcha;

use MailPoet\Subscription\Captcha\CaptchaRenderer;
use MailPoet\Subscription\Captcha\CaptchaSession;

class CaptchaRendererTest extends \MailPoetTest {
  private CaptchaRenderer $testee;
  private CaptchaSession $session;

  public function _before() {
    $this->testee = $this->diContainer->get(CaptchaRenderer::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function testItRendersImage(): void {
    $sessionId = '123';
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'a']);
    $result = $this->testee->renderImage($sessionId, null, null, true);
    $this->assertStringContainsString('JPEG', $result);
  }

  public function testItRendersAudio(): void {
    $sessionId = '123';
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'a']);
    $result = $this->testee->renderAudio($sessionId, true);
    $partOfAudio = '(-1166::BBKKQQVZZ^^bbggkkoosxx|';
    $this->assertStringContainsString($partOfAudio, $result);
  }
}
