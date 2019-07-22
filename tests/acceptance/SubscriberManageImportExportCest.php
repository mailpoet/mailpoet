<?php

namespace MailPoet\Test\Acceptance;

class SubscriberManageImportExportCest {
  function importUsersToSubscribersViaCSV(\AcceptanceTester $I) {
    $I->wantTo('Import a subscriber list from CSV');
    $I->login();
    $I->amOnMailPoetPage ('Subscribers');
    $I->click('[data-automation-id="import-subscribers-button"]');
    $this->uploadCsvFile($I);
    $I->waitForText('2 records had issues and were skipped');
    $I->click('[data-automation-id="show-more-details"]');
    $I->waitForText('1 emails are not valid:');
    $I->waitForText('1 role-based addresses are not permitted');
    $this->chooseListAndConfirm($I);
    $I->see('9 subscribers added to');
    // Test reimporting the same list
    $I->click('Import again');
    $this->uploadCsvFile($I);
    $this->chooseListAndConfirm($I);
    $I->see('9 existing subscribers were updated and added to');

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

  private function uploadCsvFile(\AcceptanceTester $I) {
    $I->waitForText('Upload a file');
    $I->click('[data-automation-id="import-csv-method"]');
    $I->attachFile('[data-automation-id="import-file-upload-input"]', 'MailPoetImportList.csv');
    $I->click('[data-automation-id="import-next-step"]');
  }

  private function chooseListAndConfirm(\AcceptanceTester $I) {
    $I->waitForText('Pick one or more list');
    // trigger dropdown to display selections
    $I->click('input.select2-search__field');
    // choose first list
    $I->click(['xpath' => '//*[@id="select2-mailpoet_segments_select-results"]/li[1]']);
    $I->click('.mailpoet_data_manipulation_step [data-automation-id="import-next-step"]');
    $I->waitForText('Import again');
  }
}
