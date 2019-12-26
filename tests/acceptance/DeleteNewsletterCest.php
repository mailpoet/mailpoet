<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNewsletterCest {

  public function moveNewsletterToTrash(\AcceptanceTester $I) {
    $newsletter_name = 'Trash Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletter_name)->create();
    $I->wantTo('Move a newsletter to trash');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Move to trash');
    $I->waitForText('1 email was moved to the trash.');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
  }

  public function restoreFormFromTrash(\AcceptanceTester $I) {
    $newsletter_name = 'Restore Trashed Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletter_name)->withDeleted()->create();
    $I->wantTo('Restore a newsletter from trash');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Restore');
    $I->waitForText('1 email has been restored from the Trash.');
    $I->waitForElement('[data-automation-id="filters_all"]');
    $I->click('[data-automation-id="filters_all"]');
    $I->waitForText($newsletter_name);
  }

  public function deleteFormPermanently(\AcceptanceTester $I) {
    $newsletter_name = 'Goodbye Forever Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletter_name)->withDeleted()->create();
    $I->wantTo('Forever delete a newsletter');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Delete Permanently');
    $I->waitForText('1 email was permanently deleted.');
    $I->waitForElementNotVisible($newsletter_name);
  }
}
