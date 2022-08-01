<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class SendPageSenderDomainCheckCest {
  public function checkSendedDomain(\AcceptanceTester $i) {
    (new Settings())->withSendingMethodMailPoet();
    $i->wantTo('check sender domain error is displayed on send page');

    $newsletter = (new Newsletter())
      ->withSubject('Draft newsletter')
      ->create();

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');

    $dmarcError = 'Email violates Sender Domain’s DMARC policy.';
    $i->cantSee($dmarcError);
    $i->clearField('[id="field_sender_address"]');
    $i->fillField('[id="field_sender_address"]', 'something@automattic.com');
    $i->click('[id="field_sender_name"]'); // Go to different field to trigger blur
    $i->waitForText($dmarcError);
    $i->click('sender authentication');
    $i->waitForText('Manage Sender Domain');
    $dnsVerifyError = 'Some DNS records were not set up correctly.';
    $i->dontSee($dnsVerifyError);
    $i->click('Verify the DNS records');
    $i->waitForText($dnsVerifyError);
  }
}
