<?php
namespace MailPoet\Test\Subscription;

use MailPoet\Subscription\CaptchaSession;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaSessionTest extends \MailPoetTest {
  const SESSION_ID = 'ABCD';

  /** @var CaptchaSession */
  private $captcha_session;

  function _before() {
    $this->captcha_session = new CaptchaSession(new WPFunctions);
    $this->captcha_session->init(self::SESSION_ID);
  }

  function testItCanStoreAndRetrieveFormData() {
    $form_data = ['email' => 'email@example.com'];
    $this->captcha_session->setFormData($form_data);
    expect($this->captcha_session->getFormData())->equals($form_data);
  }

  function testItCanStoreAndRetrieveCaptchaHash() {
    $hash = '1234';
    $this->captcha_session->setCaptchaHash($hash);
    expect($this->captcha_session->getCaptchaHash())->equals($hash);
  }

  function testItCanResetSessionData() {
    $this->captcha_session->setFormData(['email' => 'email@example.com']);
    $this->captcha_session->setCaptchaHash('hash123');
    $this->captcha_session->reset();
    expect($this->captcha_session->getFormData())->false();
    expect($this->captcha_session->getCaptchaHash())->false();
  }

  function testItAssociatesDataWithSession() {
    $hash = '1234';
    $this->captcha_session->setCaptchaHash($hash);
    expect($this->captcha_session->getCaptchaHash())->equals($hash);
    $this->captcha_session->init();
    expect($this->captcha_session->getCaptchaHash())->false();
    $this->captcha_session->init(self::SESSION_ID);
    expect($this->captcha_session->getCaptchaHash())->equals($hash);
  }
}
