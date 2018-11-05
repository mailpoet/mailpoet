<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

require_once __DIR__ . '/../DataFactories/Segment.php';
require_once __DIR__ . '/../_data/MailPoetExportList.csv';

class ExportSubscribersCest {
  function __construct() {
    $this->search_field_element = 'input.select2-search__field';
  }
  function exportSubscribers(\AcceptanceTester $I) {
    $segment_name = 'Hobbyists';
    $segment = new Segment();
    $segment->withName($segment_name)->create();
    $I->wantTo('Export a list of subscribers');
    $I->login();
    $I->amOnMailPoetPage('Subscribers');
    //import unique user list to export, so we have exact data regardless of other tests
    $I->click('[data-automation-id="import_subscribers_button"]');
    $I->waitForText('Back to Subscribers', 10);
    //select upload file as import method, import CSV
    $I->click('[data-automation-id="upload_subscriber_csv_file"]');
    $I->attachFile(['css'=>'#file_local'], 'MailPoetExportList.csv');
    $I->click(['xpath'=>'//*[@id="method_file"]/div/table/tbody/tr[2]/th/a']);
    $I->waitForText('Jokūbas', 25);
    //click is to trigger dropdown to display selections, least restrictive working method
    $I->click($this->search_field_element);
    $I->click(['xpath'=>'//*[@id="select2-mailpoet_segments_select-results"]/li[1]']);
    //click next step
    $I->click(['xpath'=>'//*[@id="step2_process"]']);
    $I->waitForText('Import again', 10);
    //export those users
    $I->amOnMailPoetPage('Subscribers');
    $I->click(['xpath'=>'//*[@id="mailpoet_export_button"]']);
    //choose new list
    $I->selectOptionInSelect2($segment_name);
    //export
    $I->click(['xpath'=>'//*[@id="mailpoet_subscribers_export"]/div[2]/table/tbody/tr[4]/th/a']);
    $I->waitForText('9 subscribers were exported. Get the exported file here.');
    $I->seeNoJSErrors();
  }
}