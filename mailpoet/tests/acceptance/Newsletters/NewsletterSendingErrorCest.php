<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class NewsletterSendingErrorCest {
  public function generalErrorNotice(\AcceptanceTester $i) {
    (new Newsletter())->create();

    $i->wantTo('See proper sending error when sending failed');
    $i->login();

    $errorMessage = 'Error while sending email.';
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_SMTP);
    $settings->withSendingError($errorMessage);

    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('div.mailpoet_notice.notice-error');
    $i->see('Sending has been paused due to a technical issue with SMTP: ' . $errorMessage, '.notice-error p');
    $i->see('Check your sending method settings.', '.notice-error p');
    $i->see('Resume sending', '.notice-error p');

    $href = $i->grabAttributeFrom('//a[text()="sending method settings"]', 'href');
    expect($href)->endsWith('page=mailpoet-settings#mta');

    $i->click('Resume sending');
    $i->waitForText('Sending has been resumed.');
  }

  public function phpMailErrorNotice(\AcceptanceTester $i) {
    (new Newsletter())->create();

    $i->wantTo('See proper sending error when sending failed with PHPMail');
    $i->login();

    $errorMessage = 'Could not instantiate mail function. Unprocessed subscriber: (test <test@test.test>)';
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_PHPMAIL);
    $settings->withSendingError($errorMessage);

    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('div.mailpoet_notice.notice-error');
    $i->see('Sending has been paused due to a technical issue with PHPMail: ' . $errorMessage, '.notice-error p');
    $i->see('Please check your sending method configuration, you may need to consult with your hosting company.', '.notice-error p');
    $i->see('The easy alternative is to send emails with MailPoet Sending Service instead, like thousands of other users do.', '.notice-error p');
    $i->see('Sign up for free in minutes', '.notice-error p');
    $i->see('Resume sending', '.notice-error p');

    $i->seeElement('a', [
      'href' => 'https://www.mailpoet.com/free-plan?utm_campaign=sending-error&utm_source=plugin',
      'target' => '_blank',
    ]);

    $i->click('Resume sending');
    $i->waitForText('Sending has been resumed.');
  }
}
