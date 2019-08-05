<?php
namespace MailPoet\Config;

use MailPoet\Util\Cookies;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class SessionTest extends \MailPoetUnitTest {

  /** @var Session */
  private $session;

  /** @var MockObject */
  private $cookies_mock;

  function _before() {
    parent::_before();
    unset($_COOKIE[Session::COOKIE_NAME]);
    $this->cookies_mock = $this->createMock(Cookies::class);
    $this->session = new Session($this->cookies_mock);
  }

  function testItInitializesNewSessionCorrectly() {
    $this->cookies_mock
      ->expects($this->once())
      ->method('get')
      ->willReturn(null);
    $this->cookies_mock
      ->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo(Session::COOKIE_NAME),
        $this->isType('string'),
        $this->callback(function ($options) {
          if (!isset($options['expires']) || $options['expires'] < time()) {
            return false;
          }
          if ($options['path'] !== '/') {
            return false;
          }
          return true;
        })
      );
    $this->session->init();
  }

  function testItPrologsCurrentSessionDuringInitialization() {
    $session_id = 'abcd';
    $this->cookies_mock
      ->expects($this->once())
      ->method('get')
      ->willReturn($session_id);
    $this->cookies_mock
      ->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo(Session::COOKIE_NAME),
        $this->equalTo($session_id),
        $this->callback(function ($options) {
          if (!isset($options['expires']) || $options['expires'] < time()) {
            return false;
          }
          if ($options['path'] !== '/') {
            return false;
          }
          return true;
        })
      );
    $this->session->init();
  }

  function testItReturnsSessionIdCorrectly() {
    $session_id = 'abcd';
    $this->cookies_mock
      ->expects($this->once())
      ->method('get')
      ->willReturn($session_id);
    expect($this->session->getid())->equals($session_id);
  }
}
