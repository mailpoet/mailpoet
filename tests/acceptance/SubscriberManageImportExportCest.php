<?php

namespace MailPoet\Test\Acceptance;

class SubscriberManageImportExportCest {

  public function importBigUsersListCSV(\AcceptanceTester $I) {
    $I->wantTo('Import a big list');
    $I->login();
    $I->amOnUrl(\AcceptanceTester::WP_URL . '/wp-admin/admin.php?page=mailpoet-import');
    $this->uploadCsvFile($I, 'MailPoetImportBigList.csv');

    // I see validation step, select a wrong source and should be blocked
    $I->waitForElement('[data-automation-id="mailpoet_import_validation_step"]');
    $I->checkOption('[data-automation-id="mailpoet_import_validation_step_option2"]');
    $I->click('[data-automation-id="import-next-step"]');
    $I->waitForElement('[data-automation-id="import_wrong_source_block"]');

    // Repeat the test, this time choose the right source, but say you sent to the list long time ago
    $I->amOnUrl(\AcceptanceTester::WP_URL . '/wp-admin/admin.php?page=mailpoet-import');
    $this->uploadCsvFile($I, 'MailPoetImportBigList.csv');
    $I->waitForElement('[data-automation-id="mailpoet_import_validation_step"]');
    $I->checkOption('[data-automation-id="mailpoet_import_validation_step_option1"]');
    $I->click('[data-automation-id="import-next-step"]');
    $I->waitForElement('[data-automation-id="last_sent_to_list"]');
    $I->selectOption('[data-automation-id="last_sent_to_list"]', 'over2years');
    $I->click('[data-automation-id="last_sent_to_list_next"]');
    $I->waitForElement('[data-automation-id="import_old_list_block"]');

    // Repeat the test, happy path
    $I->amOnUrl(\AcceptanceTester::WP_URL . '/wp-admin/admin.php?page=mailpoet-import');
    $this->uploadCsvFile($I, 'MailPoetImportBigList.csv');
    $I->waitForElement('[data-automation-id="mailpoet_import_validation_step"]');
    $I->checkOption('[data-automation-id="mailpoet_import_validation_step_option1"]');
    $I->click('[data-automation-id="import-next-step"]');
    $I->waitForElement('[data-automation-id="last_sent_to_list"]');
    $I->selectOption('[data-automation-id="last_sent_to_list"]', 'less3months');
    $I->click('[data-automation-id="last_sent_to_list_next"]');
    $I->waitForElement('[data-automation-id="import_data_manipulation_step"]');
  }

  public function importUsersToSubscribersViaCSV(\AcceptanceTester $I) {
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
    $I->searchFor('aaa@example.com');
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

  private function uploadCsvFile(\AcceptanceTester $I, $file_name = 'MailPoetImportList.csv') {
    $I->waitForText('Upload a file');
    $I->click('[data-automation-id="import-csv-method"]');
    $I->attachFile('[data-automation-id="import-file-upload-input"]', $file_name);
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
