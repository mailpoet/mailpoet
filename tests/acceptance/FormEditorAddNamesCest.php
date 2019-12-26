<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorAddNamesCest {

  public function addNamesToAForm(\AcceptanceTester $I) {
    $segment_factory = new Segment();
    $segment_name = 'Fancy List';
    $segment = $segment_factory->withName($segment_name)->create();
    $form_name = 'My fancy form';
    $form = new Form();
    $form->withName($form_name)->withSegments([$segment])->create();
    $features = new Features();
    $features->withFeatureEnabled(FeaturesController::NEW_FORM_EDITOR);
    $I->wantTo('Add first and last name to the editor');
    $I->login();
    $I->amOnMailPoetPage('Forms');
    $I->waitForText($form_name);
    $I->clickItemRowActionByItemName($form_name, 'Edit');
    $I->waitForElement('[data-automation-id="form_title_input"]');

    $I->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $I->waitForElement('.editor-inserter__results .components-panel__body-toggle');
    $I->click('.editor-inserter__results .components-panel__body-toggle'); // toggle fields
    $I->click('.editor-block-list-item-mailpoet-form-first-name-input'); // add first name block to the editor
    $I->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $I->waitForElement('.editor-inserter__results .components-panel__body-toggle');
    $I->click('.editor-inserter__results div:nth-child(2) .components-panel__body-toggle'); // toggle fields, get the second field, first one is now "Most Used"
    $I->click('.editor-block-list-item-mailpoet-form-last-name-input'); // add last name block to the editor

    $I->click('[data-automation-id="form_save_button"]');
    $I->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $I->seeNoJSErrors();
    // Reload page and check data were saved
    $I->reloadPage();
    $I->waitForElement('[data-automation-id="form_title_input"]');
    $I->seeElement('[data-automation-id="editor_first_name_input"]');
    $I->seeElement('[data-automation-id="editor_last_name_input"]');
  }

}
