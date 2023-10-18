<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use MailPoet\Settings\Hosts;

class HostsTest extends \MailPoetUnitTest {
  public function testItReturnsAListOfWebHosts() {
    $webHosts = Hosts::getWebHosts();
    verify($webHosts)->notEmpty();

    foreach ($webHosts as $host) {
      verify($host['interval'])->greaterThan(0);
      verify($host['emails'])->greaterThan(0);
    }
  }

  public function testItReturnsAListOfSMTPHosts() {
    $smtpHosts = Hosts::getSMTPHosts();
    verify($smtpHosts)->notEmpty();

    foreach ($smtpHosts as $host) {
      verify($host['interval'])->greaterThan(0);
      verify($host['emails'])->greaterThan(0);
    }
  }
}
