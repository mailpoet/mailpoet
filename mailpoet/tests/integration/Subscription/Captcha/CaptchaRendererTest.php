<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription\Captcha;

use MailPoet\Subscription\Captcha\CaptchaRenderer;
use MailPoet\Subscription\Captcha\CaptchaSession;

class CaptchaRendererTest extends \MailPoetTest {


  /** @var CaptchaRenderer */
  private $testee;

  /** @var CaptchaSession */
  private $session;

  public function _before() {
    $this->testee = $this->diContainer->get(CaptchaRenderer::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function testItRendersImage() {
    $result = $this->testee->renderImage(null, null, null, true);
    verify(strpos($result, 'JPEG') !== false)->true();
    $sessionId = $this->session->getId();
    expect($sessionId)->notNull();
  }

  public function testItRendersAudio() {
    $this->session->init();
    $hashData = [
      'phrase' => 'a',
      'total_loaded' => 1,
      'loaded_by_types' => [],
    ];
    $this->session->setCaptchaHash($hashData);
    $sessionId = $this->session->getId();
    $result = $this->testee->renderAudio($sessionId, true);
    $partOfAudio = '(-1166::BBKKQQVZZ^^bbggkkoosxx|';
    verify(strpos($result, $partOfAudio) !== false)->true();
  }

  /**
   * We need to ensure that a new captcha phrase is created when reloading
   */
  public function testItChangesCaptchaAndRendersNewImageWhenReloading() {
    $this->session->init();
    $sessionId = $this->session->getId();
    $firstImage = $this->testee->renderImage(null, null, $sessionId, true);
    $firstCaptcha = $this->session->getCaptchaHash();
    $secondImage = $this->testee->renderImage(null, null, $sessionId, true);
    $secondCaptcha = $this->session->getCaptchaHash();
    verify($secondImage)->notEquals($firstImage);
    verify($firstCaptcha['phrase'])->notEquals($secondCaptcha['phrase']);
  }

  /**
   * We need to ensure that a new captcha phrase is created when reloading
   */
  public function testItChangesCaptchaAndRendersNewAudioWhenReloading() {
    $this->session->init();
    $sessionId = $this->session->getId();
    $fistAudio = $this->testee->renderAudio($sessionId, true);
    $firstCaptcha = $this->session->getCaptchaHash();
    $secondAudio = $this->testee->renderAudio($sessionId, true);
    $secondCaptcha = $this->session->getCaptchaHash();
    verify($fistAudio)->notEquals($secondAudio);
    verify($firstCaptcha['phrase'])->notEquals($secondCaptcha['phrase']);
  }

  /**
   * We need to ensure that a new captcha phrase is created when reloading
   */
  public function testItDoesNotChangeCaptchaWhenAudioRangeHeaderChanges() {
    $this->session->init();
    $sessionId = $this->session->getId();
    $fistAudio = $this->testee->renderAudio($sessionId, true);
    $firstCaptcha = $this->session->getCaptchaHash();
    $_SERVER['HTTP_RANGE'] = 'bytes:0-1';
    $secondAudio = $this->testee->renderAudio($sessionId, true);
    $secondCaptcha = $this->session->getCaptchaHash();
    unset($_SERVER['HTTP_RANGE']);
    verify($fistAudio)->equals($secondAudio);
    verify($firstCaptcha['phrase'])->equals($secondCaptcha['phrase']);
  }

  /**
   * We need to make sure that the audio presented to a listener plays the same captcha
   * the image shows.
   */
  public function testImageAndAudioStayInSync() {
    $this->session->init();
    $sessionId = $this->session->getId();
    $this->testee->renderAudio($sessionId, true);
    $audioCaptcha = $this->session->getCaptchaHash();
    $this->testee->renderImage(null, null, $sessionId, true);
    $imageCaptcha = $this->session->getCaptchaHash();
    verify($audioCaptcha['phrase'])->equals($imageCaptcha['phrase']);

    $this->testee->renderImage(null, null, $sessionId, true);
    $secondImageCaptcha = $this->session->getCaptchaHash();
    $this->testee->renderAudio($sessionId, true);
    $secondAudioCaptcha = $this->session->getCaptchaHash();
    verify($secondAudioCaptcha['phrase'])->equals($secondImageCaptcha['phrase']);
  }
}
