<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

class AfterMigrationNoticeTest extends \MailPoetTest {
  public function testItDoesntDisplayIfShouldntDisplay() {
    $notice = new AfterMigrationNotice();
    $result = $notice->init(false);
    verify($result)->empty();
  }

  public function testItDoesntDisplayIfDisabled() {
    $notice = new AfterMigrationNotice();
    $notice->disable();
    $result = $notice->init(true);
    verify($result)->empty();
  }

  public function testItDisplayIfEnabled() {
    $notice = new AfterMigrationNotice();
    $notice->enable();
    $result = $notice->init(true);
    verify($result)->notEmpty();
  }
}
