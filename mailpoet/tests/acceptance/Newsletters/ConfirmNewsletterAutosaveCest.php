<?php declare(strict_types = 1);

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
    $standardTemplate = $i->checkTemplateIsPresent(0);
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);

    // step 3 - Add subject, wait for Autosave
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->waitForText('Autosaved');
    $i->seeNoJSErrors();
  }
}
