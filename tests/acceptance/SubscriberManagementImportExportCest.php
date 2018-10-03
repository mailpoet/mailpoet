<?php

namespace MailPoet\Test\Acceptance;

require_once __DIR__ . '/../_data/MailPoetImportList.csv';

class importExportSubscribersCest {
  function __construct() {
    $this->search_field_element = 'input.select2-search__field';
  }

  function importUsersToSubscribersViaCSV(\AcceptanceTester $I){
    $I->wantTo('Import a subscriber list from CSV');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->click(['xpath'=>'//*[@id="subscribers_container"]/div/h1/a[2]']);
    $I->waitForText('Back to Subscribers', 10);
    $I->click(['css'=>'#select_method > label:nth-of-type(2)']);
    $I->attachFile(['css'=>'#file_local'], 'MailPoetImportList.csv');
    $I->click(['xpath'=>'//*[@id="method_file"]/div/table/tbody/tr[2]/th/a']);
    $I->click($this->search_field_element);
    $I->click(['xpath'=>'//*[@id="select2-mailpoet_segments_select-results"]/li[2]']);
    $I->click(['xpath'=>'//*[@id="step2_process"]']);
    $I->waitForText('Import again', 10);
    $I->amOnMailPoetPage ('Subscribers');
    $I->seeInCurrentUrl('mailpoet-subscribers#');
    $I->fillField('#search_input', 'aaa@example.com');
    $I->click('Search');
    $I->waitForText('aaa@example.com', 10);
  }

  function exportSubscribers(\AcceptanceTester $I){
    $I->wantTo('Export a subscriber list to CSV');
    $I->login();
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-export');
    $I->fillField($this->search_field_element, 'WordPress Users');
    $I->pressKey($this->search_field_element, \WebDriverKeys::ENTER);
    $I->click(['xpath'=>'//*[@id="mailpoet_subscribers_export"]/div[2]/table/tbody/tr[4]/th/a']);
    $I->waitForText('subscribers were exported');
  }
}