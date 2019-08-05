<?php
namespace MailPoet\Test\Subscription;

use MailPoet\Config\Session;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CaptchaSessionTest extends \MailPoetTest {

  /** @var CaptchaSession */
  private $captcha_session;

  /** @var MockObject */
  private $cookies_mock;

  function _before() {
    $this->cookies_mock = $this->createMock(Cookies::class);
    $this->captcha_session = new CaptchaSession(new WPFunctions, new Session($this->cookies_mock));
  }

  function testIsAvailableWhenCookieExists() {
    $this->cookies_mock
      ->method('get')
      ->willReturn('abcd');
    expect($this->captcha_session->isAvailable())->true();
  }

  function testIsNotAvailableWhenCookieDoesntExits() {
    $this->cookies_mock
      ->method('get')
      ->willReturn(null);
    expect($this->captcha_session->isAvailable())->false();
  }

  function testItCanStoreAndRetrieveFormData() {
    $this->cookies_mock
      ->method('get')
      ->willReturn('abcd');
    $form_data = ['email' => 'email@example.com'];
    $this->captcha_session->setFormData($form_data);
    expect($this->captcha_session->getFormData())->equals($form_data);
  }

  function testItCanStoreAndRetrieveCaptchaHash() {
    $this->cookies_mock
      ->method('get')
      ->willReturn('abcd');
    $hash = '1234';
    $this->captcha_session->setCaptchaHash($hash);
    expect($this->captcha_session->getCaptchaHash())->equals($hash);
  }

  function testItCanResetSessionData() {
    $this->cookies_mock
      ->method('get')
      ->willReturn('abcd');
    $this->captcha_session->setFormData(['email' => 'email@example.com']);
    $this->captcha_session->setCaptchaHash('hash123');
    $this->captcha_session->reset();
    expect($this->captcha_session->getFormData())->false();
    expect($this->captcha_session->getCaptchaHash())->false();
  }

  function testItAssociatesDataWithSession() {
    $session1 = 'abcd';
    $session2 = 'efgh';
    $this->cookies_mock
      ->method('get')
      ->willReturnOnConsecutiveCalls($session1, $session1, $session2, $session1);
    $hash = '1234';
    $this->captcha_session->setCaptchaHash($hash);
    expect($this->captcha_session->getCaptchaHash())->equals($hash);
    expect($this->captcha_session->getCaptchaHash())->false();
    expect($this->captcha_session->getCaptchaHash())->equals($hash);
  }
}
