<?php

namespace MailPoet\Test\Acceptance;

class ConfirmNewsletterAutosaveCest {

  function confirmNewsletterAutoSave(\AcceptanceTester $I) {
    $I->wantTo('Confirm autosave works as advertised');

    $newsletter_title = 'Autosave Test ' . \MailPoet\Util\Security::generateRandomString();

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $I->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $standard_template = '[data-automation-id="select_template_0"]';
    $I->waitForElement($standard_template);
    $I->see('Newsletters', ['css' => 'a.current']);
    $I->click($standard_template);

    // step 3 - Add subject, wait for Autosave
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->waitForText('Autosaved');
    $I->seeNoJSErrors();
  }

}
