<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaSession;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSessionTest extends \MailPoetTest {
  const SESSION_ID = 'ABCD';

  private CaptchaSession $captchaSession;

  public function _before() {
    $this->captchaSession = new CaptchaSession(new WPFunctions);
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
}
