<?php
use \MailPoet\Settings\Charsets;

class CharsetsCest {
  function itReturnsAListOfCharsets() {
    $charsets = Charsets::getAll();
    expect($charsets)->notEmpty();
    expect($charsets[0])->equals('UTF-8');
  }
}
