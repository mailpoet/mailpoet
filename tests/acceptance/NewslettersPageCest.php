<?php

class NewslettersPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
    $I->resizeWindow(1024, 768);
    $this->first_row = 'id("newsletters")//table/tbody/tr[2]';
    $this->timeout = 3;
  }

  function iCanSeeTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->see('Newsletters');
  }

  function iCanAddFirstNewsletter(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->see('No newsletters found');
    $I->click('New', '#newsletters');
    $I->waitForText('Subject', $this->timeout);
    $I->fillField('subject', 'first newsletter');
    $I->fillField('Body', 'some body');
    $I->click('Save');
    $I->waitForText('1 item', $this->timeout);
  }

  function iCanEditFirstNewsletter(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->moveMouseOver($this->first_row);
    $I->click('Edit', $this->first_row);
    $I->waitForText('Subject', $this->timeout);
    $I->fillField('subject', 'first edited newsletter');
    $I->click('Save');
    $I->waitForText('edited', $this->timeout);
  }

  function iCanAddSecondNewsletter(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/new');
    $I->fillField('subject', 'second newsletter');
    $I->fillField('Body', 'some body');
    $I->click('Save');
    $I->waitForText('2 item', $this->timeout);
  }

  function iCanSortNewslettersBySubject(AcceptanceTester $I) {
    $column = 'Subject';
    $I->click($column);
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSortNewslettersByCreatedDate(AcceptanceTester $I) {
    $column = 'Created on';
    $I->click($column);
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSortNewslettersByModifiedDate(AcceptanceTester $I) {
    $column = 'Last modified on';
    $I->click($column);
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSearchNewsletters(AcceptanceTester $I) {
    $search_term = 'second';
    $I->fillField('Search', $search_term);
    $I->click('Search');
    $I->waitForText($search_term, $this->timeout, $this->first_row);
    $I->waitForText('1 item', $this->timeout);
  }

  function iCanDeleteNewsletters(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->moveMouseOver($this->first_row);
    $I->click('Trash', $this->first_row);
    $I->waitForText('1 item', $this->timeout);
    $I->moveMouseOver($this->first_row);
    $I->click('Trash', $this->first_row);
    $I->waitForText('No newsletters found', $this->timeout);
  }

  function iCanSeeMobileView(AcceptanceTester $I) {
    $listing_header = 'id("newsletters")//table/thead';
    $I->resizeWindow(640, 480);
    $I->dontSee('Created on', $listing_header);
    $I->dontSee('Last modified', $listing_header);
    $I->see('Subject', $listing_header);
  }

}
