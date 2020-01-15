<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorAddNamesCest {

  public function addNamesToAForm(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    $features = new Features();
    $features->withFeatureEnabled(FeaturesController::NEW_FORM_EDITOR);
    $i->wantTo('Add first and last name to the editor');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $i->waitForElement('.editor-inserter__results .components-panel__body-toggle');
    $i->click('.editor-inserter__results .components-panel__body:nth-child(2) .components-panel__body-toggle'); // toggle fields
    $i->click('.editor-block-list-item-mailpoet-form-first-name-input'); // add first name block to the editor
    $i->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $i->waitForElement('.editor-inserter__results .components-panel__body-toggle');
    $i->click('.editor-inserter__results .components-panel__body:nth-child(3) .components-panel__body-toggle'); // toggle fields, get the second field, first one is now "Most Used"
    $i->click('.editor-block-list-item-mailpoet-form-last-name-input'); // add last name block to the editor

    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeElement('[data-automation-id="editor_first_name_input"]');
    $i->seeElement('[data-automation-id="editor_last_name_input"]');
  }

}
