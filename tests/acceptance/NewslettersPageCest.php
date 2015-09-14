<?php

use Helper\Acceptance;

class NewslettersPageCest {
  
  function _before(AcceptanceTester $I) {
    $I->login();
    $I->resizeWindow(1024, 768);
    $this->firstElementInList = '//*[@id="newsletters"]/div/div/table/tbody/tr[1]';
    $this->waitTime = 2;
  }
  
  function iCanSeeTheTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->see('Newsletters');
  }
  
  function iCanAddNewsletterFromListingPage(AcceptanceTester $I) {
    $I->waitForElement('.no-items', $this->waitTime);
    $I->click('New', '#newsletters');
    $I->fillField('Subject', 'first newsletter');
    $I->fillField('Body', 'some body');
    $I->click('Save');
    $I->waitForText('1 item', $this->waitTime);
  }
  
  function iCanAddNewsletterFromNewNewsletterPage(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters#/form');
    $I->fillField('Subject', 'second newsletter');
    $I->fillField('Body', 'some body');
    $I->click('Save');
    $I->waitForText('2 item', $this->waitTime);
  }
  
  function iCanSortNewsletterBySubject(AcceptanceTester $I) {
    $I->click('Subject');
    $I->waitForText('first', $this->waitTime, $this->firstElementInList);
    $I->click('Subject');
    $I->waitForText('second', $this->waitTime, $this->firstElementInList);
  }
  
  function iCanSortNewsletterByCreatedDate(AcceptanceTester $I) {
    $I->click('Created on');
    $I->waitForText('first', $this->waitTime, $this->firstElementInList);
    $I->click('Created on');
    $I->waitForText('second', $this->waitTime, $this->firstElementInList);
  }
  
  function iCanSearchNewsletters(AcceptanceTester $I) {
    $searchTerm = 'second';
    $I->fillField('Search', $searchTerm);
    $I->click('Search');
    $I->waitForText($searchTerm, $this->waitTime, $this->firstElementInList);
  }

  function iCanSeeMobileView(AcceptanceTester $I) {
    $listingHeadings = '//*[@id="newsletters"]/div/div/table/thead';
    $I->resizeWindow(640, 480);
    $I->dontSee('Created on', $listingHeadings);
    $I->dontSee('Last modified', $listingHeadings);
    $I->see('Subject', $listingHeadings);
  }

  function _after(AcceptanceTester $I) {
  }
}
