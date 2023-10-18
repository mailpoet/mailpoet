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
    verify($this->captchaSession->getFormData())->equals($formData);
  }

  public function testItCanStoreAndRetrieveCaptchaHash() {
    $hash = '1234';
    $this->captchaSession->setCaptchaHash($hash);
    verify($this->captchaSession->getCaptchaHash())->equals($hash);
  }

  public function testItCanResetSessionData() {
    $this->captchaSession->setFormData(['email' => 'email@example.com']);
    $this->captchaSession->setCaptchaHash('hash123');
    $this->captchaSession->reset();
    verify($this->captchaSession->getFormData())->false();
    verify($this->captchaSession->getCaptchaHash())->false();
  }

  public function testItAssociatesDataWithSession() {
    $hash = '1234';
    $this->captchaSession->setCaptchaHash($hash);
    verify($this->captchaSession->getCaptchaHash())->equals($hash);
    $this->captchaSession->init();
    verify($this->captchaSession->getCaptchaHash())->false();
    $this->captchaSession->init(self::SESSION_ID);
    verify($this->captchaSession->getCaptchaHash())->equals($hash);
  }
}
