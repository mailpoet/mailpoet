<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class SenderDomainCheckCest {
  public function checkDomainErrorAndModalAreDisplayed(\AcceptanceTester $i) {
    (new Settings())->withSendingMethodMailPoet();
    $i->wantTo('check the sender domain error is present when MSS is active and wrong domain used');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->waitForText('Basics');
    $i->seeNoJSErrors();
    $dmarcError = 'Email violates Sender Domain’s DMARC policy.';
    $i->cantSee($dmarcError);
    $i->clearField('[data-automation-id="from-email-field"]');
    $i->fillField('[data-automation-id="from-email-field"]', 'something@automattic.com');
    $i->click('[data-automation-id="from-name-field"]'); // Go to different field to trigger blur
    $i->waitForText($dmarcError);
    $i->click('sender authentication');
    $i->waitForText('Manage Sender Domain');
    $dnsVerifyError = 'Some DNS records were not set up correctly.';
    $i->dontSee($dnsVerifyError);
    $i->click('Verify the DNS records');
    $i->waitForText($dnsVerifyError);
  }
}
