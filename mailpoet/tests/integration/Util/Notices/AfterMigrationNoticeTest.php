<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

class AfterMigrationNoticeTest extends \MailPoetTest {
  public function testItDoesntDisplayIfShouldntDisplay() {
    $notice = new AfterMigrationNotice();
    $result = $notice->init(false);
    expect($result)->isEmpty();
  }

  public function testItDoesntDisplayIfDisabled() {
    $notice = new AfterMigrationNotice();
    $notice->disable();
    $result = $notice->init(true);
    expect($result)->isEmpty();
  }

  public function testItDisplayIfEnabled() {
    $notice = new AfterMigrationNotice();
    $notice->enable();
    $result = $notice->init(true);
    expect($result)->notEmpty();
  }
}
