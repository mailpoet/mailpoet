<?php

namespace MailPoet\Test\Settings;

use MailPoet\Settings\Hosts;

class HostsTest extends \MailPoetUnitTest {
  public function testItReturnsAListOfWebHosts() {
    $web_hosts = Hosts::getWebHosts();
    expect($web_hosts)->notEmpty();

    foreach ($web_hosts as $host) {
      expect($host['interval'])->greaterThan(0);
      expect($host['emails'])->greaterThan(0);
    }
  }

  public function testItReturnsAListOfSMTPHosts() {
    $smtp_hosts = Hosts::getSMTPHosts();
    expect($smtp_hosts)->notEmpty();

    foreach ($smtp_hosts as $host) {
      expect($host['interval'])->greaterThan(0);
      expect($host['emails'])->greaterThan(0);
    }
  }
}
