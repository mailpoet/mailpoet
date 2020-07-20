<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNewsletterCest {
  public function moveNewsletterToTrash(\AcceptanceTester $i) {
    $newsletterName = 'Trash Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $newsletter->withSubject($newsletterName . '2')->create();
    $newsletter->withSubject($newsletterName . '3')->create();
    $i->wantTo('Move a newsletter to trash');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->waitForText('1 email was moved to the trash.');
    // click to select all newsletters
    $i->click('.mailpoet-form-checkbox-control');
    $i->click('Move to trash');
    $i->waitForText('2 emails were moved to the trash.');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
  }

  public function restoreNewsletterFromTrash(\AcceptanceTester $i) {
    $newsletterName = 'Restore Trashed Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '3')->withDeleted()->create();
    $i->wantTo('Restore a newsletter from trash');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Restore');
    $i->waitForText('1 email has been restored from the Trash.');
    // click to select all newsletters
    $i->click('.mailpoet-form-checkbox-control');
    $i->click('Restore');
    $i->waitForText('2 emails have been restored from the Trash.');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->click('[data-automation-id="filters_all"]');
    $i->waitForText($newsletterName);
  }

  public function deleteNewsletterPermanently(\AcceptanceTester $i) {
    $newsletterName = 'Goodbye Forever Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '3')->withDeleted()->create();
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName . '4')->create();
    $i->wantTo('Forever delete a newsletter');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Delete Permanently');
    $i->waitForText('1 email was permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
    $i->waitForText($newsletterName . '2');
    $i->waitForText($newsletterName . '3');
    // click to select all newsletters
    $i->click('.mailpoet-form-checkbox-control');
    $i->click('Delete Permanently');
    $i->waitForText('2 emails were permanently deleted.');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->waitForText($newsletterName . '4');
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $newsletterName = 'Goodbye Forever Newsletter';
    $i->wantTo('Empty a trash on Newsletters page');
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName . '3')->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    $i->click('[data-automation-id="empty_trash"]');
    $i->makeScreenshot('ss2');
    $i->waitForText('2 emails were permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
    $i->click('[data-automation-id="filters_all"]');
    $i->waitForText($newsletterName . '3');
  }

  public function selectAllAvailableNewslettersAndDelete(\AcceptanceTester $i) {
    $newsletterName = 'Sample Newsletter';
    $newsletter = new Newsletter();
    for ($itemCount = 1; $itemCount <= 22; $itemCount++) {
      $newsletter->withSubject($newsletterName . $itemCount)->withSentStatus()->create();
    }
    $i->wantTo('Select all available newsletters and proceed with deletion');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    // click to select all newsletters
    $i->click('.mailpoet-form-checkbox-control');
    $i->waitForText('All emails on this page are selected.');
    $i->click('Select all emails on all pages');
    $i->waitForText('All 22 emails are selected.');
    $i->click('Move to trash');
    $i->waitForText('22 emails were moved to the trash.');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    // click to select all newsletters
    $i->click('.mailpoet-form-checkbox-control');
    $i->waitForText('All emails on this page are selected.');
    $i->click('Select all emails on all pages');
    $i->waitForText('All 22 emails are selected.');
    $i->click('Delete Permanently');
    $i->waitForText('22 emails were permanently deleted.');
  }
}