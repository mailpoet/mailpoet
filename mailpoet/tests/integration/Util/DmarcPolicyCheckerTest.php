<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\DmarcPolicyChecker;

class DmarcPolicyCheckerTest extends \MailPoetTest {
  public $dmarcPolicyChecker;

  public function __construct() {
    parent::__construct();
    $this->dmarcPolicyChecker = new DmarcPolicyChecker();
  }

  public function testItReturnsNoneWhenDomainDoesNotHaveDmarc() {
    $domain = 'example.com'; // has no TXT records
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_NONE);
  }

  public function testItReturnsQuarantineStatus() {
    $domain = 'automattic.com'; // quarantine
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_QUARANTINE);

    // testing with mailpoet.com
    $domain = 'mailpoet.com'; // quarantine
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_QUARANTINE);
  }

  public function testItReturnsRejectStatus() {
    $domain = 'google.com'; // reject
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_REJECT);
  }

  public function testItReturnsNoneForInvalidDomain() {
    $domain = 'example'; // not a valid domain name
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_NONE);
  }

  public function testItReturnsNoneForImproperDmarcSetup() {
    $domain = 'mine.com'; // used to be v=spf1 ip4:185.39.48.22/31 ip6:2a04:7a00:0:3948::22/127 -all
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_NONE);
  }

  public function testItReturnsSpStatusBeforePStatus() {
    $domain = 'gmail.com'; // used to be v=DMARC1; p=none; sp=quarantine; rua=mailto:mailauth-reports@google.com
    $result = $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
    expect($result)->equals(DmarcPolicyChecker::POLICY_QUARANTINE);
  }

  public function testItThrowsWhenFalsyDomainPassed() {
    $domain = '';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Domain is Required');
    $this->dmarcPolicyChecker->getDomainDmarcPolicy($domain);
  }
}
