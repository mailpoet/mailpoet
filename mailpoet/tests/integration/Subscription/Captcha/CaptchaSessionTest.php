<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription\Captcha;

use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSessionTest extends \MailPoetTest {
  const SESSION_ID = 'ABCD';

  /** @var CaptchaSession */
  private $captchaSession;

  public function _before() {
    $this->captchaSession = new CaptchaSession(new WPFunctions);
    $this->captchaSession->init(self::SESSION_ID);
  }

  public function testItCanStoreAndRetrieveFormData() {
    $formData = ['email' => 'email@example.com'];
    $this->captchaSession->setFormData($formData);
    expect($this->captchaSession->getFormData())->equals($formData);
  }

  public function testItCanStoreAndRetrieveCaptchaHash() {
    $hash = '1234';
    $this->captchaSession->setCaptchaHash($hash);
    expect($this->captchaSession->getCaptchaHash())->equals($hash);
  }

  public function testItCanResetSessionData() {
    $this->captchaSession->setFormData(['email' => 'email@example.com']);
    $this->captchaSession->setCaptchaHash('hash123');
    $this->captchaSession->reset();
    expect($this->captchaSession->getFormData())->false();
    expect($this->captchaSession->getCaptchaHash())->false();
  }

  public function testItAssociatesDataWithSession() {
    $hash = '1234';
    $this->captchaSession->setCaptchaHash($hash);
    expect($this->captchaSession->getCaptchaHash())->equals($hash);
    $this->captchaSession->init();
    expect($this->captchaSession->getCaptchaHash())->false();
    $this->captchaSession->init(self::SESSION_ID);
    expect($this->captchaSession->getCaptchaHash())->equals($hash);
  }
}
