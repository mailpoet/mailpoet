<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

class FormEditorUpdateNewFormCest {
  public function updateNewForm(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();
    $i->wantTo('Create and update form');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    // Create a new form
    $formName = 'My awesome form';
    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->fillField('[data-automation-id="form_title_input"]', $formName);
    // Try saving form without selected list
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Please select a list', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    // Select list and save form
    $i->selectOptionInSelect2($segmentName);
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->see($formName, '[data-automation-id="form_title_input"]');
    $i->seeSelectedInSelect2($segmentName);
    $i->seeNoJSErrors();
  }
}
