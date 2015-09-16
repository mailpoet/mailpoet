<?php

class SubscribersPageCest {
  
  function _before(AcceptanceTester $I) {
    $I->login();
    $I->resizeWindow(1024, 768);
    $this->first_row = 'id("subscribers")//table/tbody/tr[2]';
    $this->timeout = 3;
    $this->subscribers = [
      'Adam' => [
        'firstName' => 'Adam',
        'lastName' => 'Doe',
        'email' => 'abc@mailpoet.com',
        'status' => 'Unsubscribed'
      ],
      'Jane' => [
        'firstName' => 'Jane',
        'lastName' => 'Doe',
        'email' => 'def@mailpoet.com',
        'status' => 'Unconfirmed'
      ]
    ];
  }
  
  function iCanSeeTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers');
    $I->see('Subscribers');
  }
  
  function iCanAddFirstSubscriber(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers');
    $I->see('No subscribers found');
    $I->click('New', '#subscribers');
    $I->waitForText('E-mail', $this->timeout);
    $I->fillField('email', $this->subscribers['Adam']['email']);
    $I->fillField('first_name', $this->subscribers['Adam']['firstName']);
    $I->fillField('last_name', $this->subscribers['Adam']['lastName']);
    $I->selectOption('select[name=status]', $this->subscribers['Adam']['status']);
    $I->click('Save');
    $I->waitForText('1 item', $this->timeout);
    $I->see($this->subscribers['Adam']['email']);
    $I->see($this->subscribers['Adam']['firstName']);
    $I->see($this->subscribers['Adam']['lastName']);
    $I->see($this->subscribers['Adam']['status']);
  }
  
  function iCanEditFirstSubscriber(AcceptanceTester $I) {
    $this->subscribers['Adam']['status'] = 'Subscribed';
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers');
    $I->moveMouseOver($this->first_row);
    $I->click('Edit', $this->first_row);
    $I->waitForText('E-mail', $this->timeout);
    $I->selectOption('select[name=status]', $this->subscribers['Adam']['status']);
    $I->click('Save');
    $I->waitForText('1 item', $this->timeout);
    $I->see($this->subscribers['Adam']['status']);
  }
  
  function iCanAddSecondSubscriber(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers#/new');
    $I->fillField('email', $this->subscribers['Jane']['email']);
    $I->fillField('first_name', $this->subscribers['Jane']['firstName']);
    $I->fillField('last_name', $this->subscribers['Jane']['lastName']);
    $I->click('Save');
    $I->waitForText('2 item', $this->timeout);
    $I->see($this->subscribers['Jane']['email']);
    $I->see($this->subscribers['Jane']['firstName']);
    $I->see($this->subscribers['Jane']['lastName']);
    $I->see($this->subscribers['Jane']['status']);
  }
  
  function iCanSortSubscribersByEmail(AcceptanceTester $I) {
    $column = 'Email';
    $I->click($column);
    $I->waitForText($this->subscribers['Jane']['email'], $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText($this->subscribers['Adam']['email'], $this->timeout, $this->first_row);
  }
  
  function iCanSortSubscribersBySubscribedDate(AcceptanceTester $I) {
    $column = 'Subscribed on';
    $I->click($column);
    $I->waitForText('Adam', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('Jane', $this->timeout, $this->first_row);
  }

  function iCanSortSubscribersByModifiedDate(AcceptanceTester $I) {
    $column = 'Last modified on';
    $I->click($column);
    $I->waitForText('Adam', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('Jane', $this->timeout, $this->first_row);
  }

  function iCanSortSubscribersByStatus(AcceptanceTester $I) {
    $column = 'Status';
    $I->click($column);
    $I->waitForText('Adam', $this->timeout, $this->first_row);
    $I->click($column);
    $I->waitForText('Jane', $this->timeout, $this->first_row);
  }

  function iCanSearchSubscribers(AcceptanceTester $I) {
    $search_term = 'Jane';
    $I->fillField('Search', $search_term);
    $I->click('Search');
    $I->waitForText($search_term, $this->timeout, $this->first_row);
    $I->waitForText('1 item', $this->timeout);
  }


  function iCanFilterByStatus(AcceptanceTester $I) {
    $I->click('Subscribed', '.subsubsub');
    $I->waitForText('Adam', $this->timeout, $this->first_row);
    $I->click('Unconfirmed', '.subsubsub');
    $I->waitForText('Jane', $this->timeout, $this->first_row);
  }

  function iCanBulkSelectSubscribers(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers');
    $I->click('td.manage-column input');
    $I->seeNumberOfElements('th.check-column input:checked', 2);
  }

  function iCanDeleteSubscribers(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers');
    $I->moveMouseOver($this->first_row);
    $I->click('Trash', $this->first_row);
    $I->waitForText('1 item', $this->timeout);
    $I->moveMouseOver($this->first_row);
    $I->click('Trash', $this->first_row);
    $I->waitForText('No subscribers found', $this->timeout);
  }

  function iCanSeeMobileView(AcceptanceTester $I) {
    $listing_header = 'id("subscribers")//table/thead';
    $I->resizeWindow(640, 480);
    $I->dontSee('Subscribed on', $listing_header);
    $I->dontSee('Last modified on', $listing_header);
    $I->dontSee('Firstname', $listing_header);
    $I->dontSee('Lastname', $listing_header);
    $I->dontSee('Status', $listing_header);
    $I->see('Email', $listing_header);
  }

}
