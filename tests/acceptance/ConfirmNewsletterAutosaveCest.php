<?php

namespace MailPoet\Test\Acceptance;

class ConfirmNewsletterAutosaveCest {
  public function confirmNewsletterAutoSave(\AcceptanceTester $i) {
    $i->wantTo('Confirm autosave works as advertised');

    $newsletterTitle = 'Autosave Test ' . \MailPoet\Util\Security::generateRandomString();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $i->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $standardTab = '[data-automation-id="templates-standard"]';
    $i->waitForElement($standardTab);
    $i->click($standardTab);
    $standardTemplate = '[data-automation-id="select_template_0"]';
    $i->waitForElement($standardTemplate);
    $i->see('Newsletters', ['css' => 'a.current']);
    $i->click($standardTemplate);

    // step 3 - Add subject, wait for Autosave
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->waitForText('Autosaved');
    $i->seeNoJSErrors();
  }
}
