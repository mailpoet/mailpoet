<?php

use Helper\Acceptance;

class NewslettersPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
    $I->resizeWindow(1024, 768);
    $this->first_row = 'id("newsletters")//table/tbody/tr[2]';
    $this->timeout = 3;
  }

  function iCanSeeTheTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->see('Newsletters');
  }

  function iCanAddANewsletter(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->see('No newsletters found');
    $I->click('New', '#newsletters');
    $I->fillField('subject', 'first newsletter');
    $I->fillField('Body', 'some body');
    $I->click('Save');
    $I->waitForText('1 item', $this->timeout);
  }

  function iCanAddAnotherNewsletter(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/new');
    $I->fillField('subject', 'second newsletter');
    $I->fillField('Body', 'some body');
    $I->click('Save');
    $I->waitForText('2 item', $this->timeout);
  }

  function iCanSortNewsletterBySubject(AcceptanceTester $I) {
    $I->click('Subject');
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click('Subject');
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSortNewsletterByCreatedDate(AcceptanceTester $I) {
    $I->click('Created on');
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click('Created on');
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSearchNewsletters(AcceptanceTester $I) {
    $search_term = 'second';
    $I->fillField('Search', $search_term);
    $I->click('Search');
    $I->waitForText($search_term, $this->timeout, $this->first_row);
  }

  function iCanSeeMobileView(AcceptanceTester $I) {
    $listing_header = 'id("newsletters")//table/thead';
    $I->resizeWindow(640, 480);
    $I->dontSee('Created on', $listing_header);
    $I->dontSee('Last modified', $listing_header);
    $I->see('Subject', $listing_header);
  }

  function _after(AcceptanceTester $I) {
  }
}
