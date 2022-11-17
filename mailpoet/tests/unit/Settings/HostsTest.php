<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use MailPoet\Settings\Hosts;

class HostsTest extends \MailPoetUnitTest {
  public function testItReturnsAListOfWebHosts() {
    $webHosts = Hosts::getWebHosts();
    expect($webHosts)->notEmpty();

    foreach ($webHosts as $host) {
      expect($host['interval'])->greaterThan(0);
      expect($host['emails'])->greaterThan(0);
    }
  }

  public function testItReturnsAListOfSMTPHosts() {
    $smtpHosts = Hosts::getSMTPHosts();
    expect($smtpHosts)->notEmpty();

    foreach ($smtpHosts as $host) {
      expect($host['interval'])->greaterThan(0);
      expect($host['emails'])->greaterThan(0);
    }
  }
}
