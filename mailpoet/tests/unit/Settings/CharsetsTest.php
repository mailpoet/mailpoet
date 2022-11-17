<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use MailPoet\Settings\Charsets;

class CharsetsTest extends \MailPoetUnitTest {
  public function testItReturnsAListOfCharsets() {
    $charsets = Charsets::getAll();
    expect($charsets)->notEmpty();
    expect($charsets[0])->equals('UTF-8');
  }
}
