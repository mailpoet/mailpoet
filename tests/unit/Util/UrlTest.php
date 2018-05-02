<?php
namespace MailPoet\Test\Util;

use MailPoet\Util\Url;

class UrlTest extends \MailPoetTest {
  function testCurrentUrlReturnsHomeUrlOnHome() {
    $current_url = Url::getCurrentUrl();
    $home_url = home_url();
    expect($current_url)->equals($home_url);
  }
}