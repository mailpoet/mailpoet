<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class NewsletterSendingErrorCest {
  function generalErrorNotice(\AcceptanceTester $I) {
    (new Newsletter())->create();

    $I->wantTo('See proper sending error when sending failed');
    $I->login();

    $errorMessage = 'Error while sending email.';
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_SMTP);
    $settings->withSendingError($errorMessage);

    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('div.mailpoet_notice.notice-error');
    $I->see('Sending has been paused due to a technical issue with SMTP: ' . $errorMessage, '.notice-error p');
    $I->see('Check your sending method settings.', '.notice-error p');
    $I->see('Resume sending', '.notice-error p');

    $href = $I->grabAttributeFrom('//a[text()="sending method settings"]', 'href');
    expect($href)->endsWith('page=mailpoet-settings#mta');

    $I->click('Resume sending');
    $I->waitForText('Sending has been resumed.');
  }

  function phpMailErrorNotice(\AcceptanceTester $I) {
    (new Newsletter())->create();

    $I->wantTo('See proper sending error when sending failed with PHPMail');
    $I->login();

    $errorMessage = 'Could not instantiate mail function. Unprocessed subscriber: (test <test@test.test>)';
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_PHPMAIL);
    $settings->withSendingError($errorMessage);

    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('div.mailpoet_notice.notice-error');
    $I->see('Sending has been paused due to a technical issue with PHPMail: ' . $errorMessage, '.notice-error p');
    $I->see('Please check your sending method configuration, you may need to consult with your hosting company.', '.notice-error p');
    $I->see('The easy alternative is to send emails with MailPoet Sending Service instead, like thousands of other users do.', '.notice-error p');
    $I->see('Sign up for free in minutes', '.notice-error p');
    $I->see('Resume sending', '.notice-error p');

    $I->seeElement('a', [
      'text' => 'Sign up for free in minutes',
      'href' => 'https://www.mailpoet.com/free-plan?utm_campaign=sending-error&utm_source=plugin',
      'target' => '_blank',
    ]);

    $I->click('Resume sending');
    $I->waitForText('Sending has been resumed.');
  }
}
