<?php

class SegmentsPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
    $I->resizeWindow(1024, 768);
    $this->first_row = 'id("segments")//table/tbody/tr[2]';
    $this->timeout = 3;
  }

  function iCanSeeTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-segments');
    $I->see('Segments');
  }

  function iCanAddFirstSegment(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-segments');
    $I->see('No segments found');
    $I->click('New', '#segments');
    $I->waitForText('Name', $this->timeout);
    $I->fillField('name', 'first segment');
    $I->click('Save');
    $I->waitForText('1 item', $this->timeout);
  }

  function iCanEditFirstSegment(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-segments');
    $I->moveMouseOver($this->first_row);
    $I->click('Edit', $this->first_row);
    $I->waitForText('Name', $this->timeout);
    $I->fillField('name', 'first edited segment');
    $I->click('Save');
    $I->waitForText('edited', $this->timeout);
  }

  function iCanAddSecondSegment(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-segments#/new');
    $I->fillField('name', 'second segment');
    $I->click('Save');
    $I->waitForText('2 item', $this->timeout);
  }

  function iCanSortSegmentsByName(AcceptanceTester $I) {
    $column = 'Name';
    $I->click($column);
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSortSegmentsByCreatedDate(AcceptanceTester $I) {
    $column = 'Created on';
    $I->click($column);
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSortSegmentsByModifiedDate(AcceptanceTester $I) {
    $column = 'Last modified on';
    $I->click($column);
    $I->waitForText('first', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('second', $this->timeout, $this->first_row);
  }

  function iCanSearchSegments(AcceptanceTester $I) {
    $search_term = 'second';
    $I->fillField('Search', $search_term);
    $I->click('Search');
    $I->waitForText($search_term, $this->timeout, $this->first_row);
    $I->waitForText('1 item', $this->timeout);
  }

  function iCanDeleteSegments(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-segments');
    $I->moveMouseOver($this->first_row);
    $I->click('Trash', $this->first_row);
    $I->waitForText('1 item', $this->timeout);
    $I->moveMouseOver($this->first_row);
    $I->click('Trash', $this->first_row);
    $I->waitForText('No segments found', $this->timeout);
  }

  function iCanSeeMobileView(AcceptanceTester $I) {
    $listing_header = 'id("segments")//table/thead';
    $I->resizeWindow(640, 480);
    $I->dontSee('Created on', $listing_header);
    $I->dontSee('Last modified', $listing_header);
    $I->see('Name', $listing_header);
  }

}
