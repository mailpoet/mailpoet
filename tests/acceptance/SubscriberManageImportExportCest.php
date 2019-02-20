<?php

namespace MailPoet\Test\Acceptance;

require_once __DIR__ . '/../_data/MailPoetImportList.csv';

class SubscriberManageImportExportCest {
  function importUsersToSubscribersViaCSV(\AcceptanceTester $I) {
    $I->wantTo('Import a subscriber list from CSV');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    //click import
    $I->click(['xpath'=>'//*[@id="subscribers_container"]/div/h1/a[2]']);
    $I->waitForText('Back to Subscribers');
    //select upload file as import method, import CSV
    $I->click(['css'=>'#select_method > label:nth-of-type(2)']);
    $I->attachFile(['css'=>'#file_local'], 'MailPoetImportList.csv');
    $I->click(['xpath'=>'//*[@id="method_file"]/div/table/tbody/tr[2]/th/a']);
    //click is to trigger dropdown to display selections
    $I->click('input.select2-search__field');
    //choose My First List
    $I->click(['xpath'=>'//*[@id="select2-mailpoet_segments_select-results"]/li[2]']);
    //click next step
    $I->click(['xpath'=>'//*[@id="step2_process"]']);
    $I->waitForText('Import again');
    //confirm subscribers from import list were added
    $I->amOnMailPoetPage ('Subscribers');
    $I->searchFor('aaa@example.com', 2);
    $I->waitForText('aaa@example.com');
    $I->searchFor('bbb@example.com');
    $I->waitForText('bbb@example.com');
    $I->searchFor('ccc@example.com');
    $I->waitForText('ccc@example.com');
    $I->searchFor('ddd@example.com');
    $I->waitForText('ddd@example.com');
    $I->searchFor('eee@example.com');
    $I->waitForText('eee@example.com');
    $I->searchFor('fff@example.com');
    $I->waitForText('fff@example.com');
    $I->searchFor('ggg@example.com');
    $I->waitForText('ggg@example.com');
    $I->searchFor('hhh@example.com');
    $I->waitForText('hhh@example.com');
    $I->searchFor('iii@example.com');
    $I->waitForText('iii@example.com');
    $I->seeNoJSErrors();
  }
}
