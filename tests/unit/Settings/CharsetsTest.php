<?php
use \MailPoet\Settings\Charsets;

class CharsetsTest extends MailPoetTest {
  function testItReturnsAListOfCharsets() {
    $charsets = Charsets::getAll();
    expect($charsets)->notEmpty();
    expect($charsets[0])->equals('UTF-8');
  }
}
