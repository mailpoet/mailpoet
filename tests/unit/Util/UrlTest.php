<?php
use \MailPoet\Util\Url;

class UtilUrlTest extends MailPoetTest {
  function testItCanReturnCurrentUrl() {
    $current_url = Url::getCurrentUrl();
    expect($current_url)->startsWith('http');
  }
}