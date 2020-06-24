<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DeleteNewsletterCest {
  public function moveNewsletterToTrash(\AcceptanceTester $i) {
    $newsletterName = 'Trash Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $i->wantTo('Move a newsletter to trash');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Move to trash');
    $i->waitForText('1 email was moved to the trash.');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
  }

  public function restoreFormFromTrash(\AcceptanceTester $i) {
    $newsletterName = 'Restore Trashed Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $i->wantTo('Restore a newsletter from trash');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Restore');
    $i->waitForText('1 email has been restored from the Trash.');
    $i->waitForElement('[data-automation-id="filters_all"]');
    $i->click('[data-automation-id="filters_all"]');
    $i->waitForText($newsletterName);
  }

  public function deleteFormPermanently(\AcceptanceTester $i) {
    $newsletterName = 'Goodbye Forever Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName . '2')->withDeleted()->create();
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
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $newsletterName = 'Goodbye Forever Newsletter';
    $i->wantTo('Empty a trash on Newsletters page');
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->withDeleted()->create();
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName . '2')->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="filters_trash"]');
    $i->click('[data-automation-id="filters_trash"]');
    $i->waitForText($newsletterName);

    $i->click('[data-automation-id="empty_trash"]');

    $i->waitForText('1 email was permanently deleted.');
    $i->waitForElementNotVisible($newsletterName);
    $i->click('[data-automation-id="filters_all"]');

    $i->waitForText($newsletterName . '2');
  }
}
