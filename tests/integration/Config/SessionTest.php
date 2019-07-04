<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\Session;

class SessionTest extends \MailPoetTest {
  function _before() {
    $this->destroySessionIfExists();
  }

  function testItStartsSessionIfItIsNotStarted() {
    expect(session_id())->isEmpty();
    $session = new Session;
    $result = $session->init();
    expect($result)->equals(true);
    expect(session_id())->notEmpty();
    session_destroy();
  }

  function testItDoesNotStartSessionIfItIsAlreadyStarted() {
    session_start();
    expect(session_id())->notEmpty();
    $session = new Session;
    $result = $session->init();
    expect($result)->equals(false);
    expect(session_id())->notEmpty();
    session_destroy();
  }

  private function destroySessionIfExists() {
    if (session_id()) {
      session_destroy();
    }
  }
}
