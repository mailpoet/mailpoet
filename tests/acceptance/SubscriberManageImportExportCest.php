<?php

namespace MailPoet\Test\Acceptance;

require_once __DIR__ . '/../_data/MailPoetImportList.csv';

class subscriberManageImportExportCest {
  function __construct() {
    $this->search_field_element = 'input.select2-search__field';
  }

  function importUsersToSubscribersViaCSV(\AcceptanceTester $I){
    $I->wantTo('Import a subscriber list from CSV');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    //click import
    $I->click(['xpath'=>'//*[@id="subscribers_container"]/div/h1/a[2]']);
    $I->waitForText('Back to Subscribers', 10);
    //select upload file as import method, import CSV
    $I->click(['css'=>'#select_method > label:nth-of-type(2)']);
    $I->attachFile(['css'=>'#file_local'], 'MailPoetImportList.csv');
    $I->click(['xpath'=>'//*[@id="method_file"]/div/table/tbody/tr[2]/th/a']);
    //click is to trigger dropdown to display selections
    $I->click($this->search_field_element);
    //choose My First List
    $I->click(['xpath'=>'//*[@id="select2-mailpoet_segments_select-results"]/li[2]']);
    //click next step
    $I->click(['xpath'=>'//*[@id="step2_process"]']);
    $I->waitForText('Import again', 10);
    //confirm subscribers from import list were added
    $I->amOnMailPoetPage ('Subscribers');
    $I->seeInCurrentUrl('mailpoet-subscribers#');
    $I->fillField('#search_input', 'aaa@example.com');
    $I->click('Search');
    $I->waitForText('aaa@example.com', 10);
    $I->fillField('#search_input', 'bbb@example.com');
    $I->click('Search');
    $I->waitForText('bbb@example.com', 10);
    $I->fillField('#search_input', 'ccc@example.com');
    $I->click('Search');
    $I->waitForText('ccc@example.com', 10);
    $I->fillField('#search_input', 'ddd@example.com');
    $I->click('Search');
    $I->waitForText('ddd@example.com', 10);
    $I->fillField('#search_input', 'eee@example.com');
    $I->click('Search');
    $I->waitForText('eee@example.com', 10);
    $I->fillField('#search_input', 'fff@example.com');
    $I->click('Search');
    $I->waitForText('fff@example.com', 10);
    $I->fillField('#search_input', 'ggg@example.com');
    $I->click('Search');
    $I->waitForText('ggg@example.com', 10);
    $I->fillField('#search_input', 'hhh@example.com');
    $I->click('Search');
    $I->waitForText('hhh@example.com', 10);
    $I->fillField('#search_input', 'iii@example.com');
    $I->click('Search');
    $I->waitForText('iii@example.com', 10);
  }
}