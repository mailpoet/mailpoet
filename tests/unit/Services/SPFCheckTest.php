<?php

namespace MailPoet\Services;

class SPFCheckTest extends \MailPoetUnitTest {
  function testItChecksSPFRecord() {
    $domain = 'example.com';
    // Failed to get DNS records
    $response = false;
    $check = $this->make(SPFCheck::class, ['dnsGetRecord' => $response]);
    expect($check->checkSPFRecord($domain))->equals(true);
    // No SPF record
    $response = [['txt' => '123'], ['txt' => 'abc']];
    $check = $this->make(SPFCheck::class, ['dnsGetRecord' => $response]);
    expect($check->checkSPFRecord($domain))->equals(true);
    // Good SPF record
    $response = [['txt' => 'v=spf1 include:spf.protection.outlook.com include:sendgrid.net include:spf.sendingservice.net -all']];
    $check = $this->make(SPFCheck::class, ['dnsGetRecord' => $response]);
    expect($check->checkSPFRecord($domain))->equals(true);
    // Bad SPF record
    $response = [['txt' => 'v=spf1 include:spf.protection.outlook.com include:sendgrid.net -all']];
    $check = $this->make(SPFCheck::class, ['dnsGetRecord' => $response]);
    expect($check->checkSPFRecord($domain))->equals(false);
  }
}
