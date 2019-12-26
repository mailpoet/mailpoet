<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Segment;

class FormEditorUpdateNewFormCest {
  public function updateNewForm(\AcceptanceTester $I) {
    $segment_factory = new Segment();
    $segment_name = 'Fancy List';
    $segment_factory->withName($segment_name)->create();
    $features = new Features();
    $features->withFeatureEnabled(FeaturesController::NEW_FORM_EDITOR);
    $I->wantTo('Create and update form');
    $I->login();
    $I->amOnMailPoetPage('Forms');
    // Create a new form
    $form_name = 'My awesome form';
    $I->click('[data-automation-id="create_new_form"]');
    $I->waitForElement('[data-automation-id="form_title_input"]');
    $I->fillField('[data-automation-id="form_title_input"]', $form_name);
    // Try saving form without selected list
    $I->click('[data-automation-id="form_save_button"]');
    $I->waitForText('Please select a list', 10, '.automation-dismissible-notices');
    $I->seeNoJSErrors();
    // Select list and save form
    $I->selectOptionInSelect2($segment_name);
    $I->click('[data-automation-id="form_save_button"]');
    $I->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $I->seeNoJSErrors();
    // Reload page and check data were saved
    $I->reloadPage();
    $I->waitForElement('[data-automation-id="form_title_input"]');
    $I->see($form_name, '[data-automation-id="form_title_input"]');
    $I->seeSelectedInSelect2($segment_name);
    $I->seeNoJSErrors();
  }
}
