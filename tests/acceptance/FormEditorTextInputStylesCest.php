<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorTextInputStylesCest {
  public function changeTextInputStyles(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->withDisplayBelowPosts()->create();
    $i->wantTo('Set text input styles');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    // Add first name
    $i->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $i->waitForElement('.block-editor-inserter__results .components-panel__body-toggle');
    $i->click('.block-editor-inserter__results .components-panel__body:nth-child(2) .components-panel__body-toggle'); // toggle fields
    $i->click('.editor-block-list-item-mailpoet-form-first-name-input'); // add first name block to the editor

    // Apply some styles to first name
    $i->click('.mailpoet-automation-input-styles-panel');
    $i->waitForElement('[data-automation-id="input_styles_settings"]');
    $i->click('.mailpoet-automation-inherit-theme-toggle input'); // Display custom settings
    $i->click('.mailpoet-automation-styles-bold-toggle input'); // Toggle bold on
    $i->fillField('.mailpoet-automation-styles-border-size input[type="number"]', 10); // Set border size
    $i->click('.mailpoet-automation-label-within-input-toggle input'); // Toggle lable to be rendered outside the input

    // Check element has styles
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_label"]', 'style', 'font-weight: bold;');
    // Apply to all
    $i->click('[data-automation-id="styles_apply_to_all"]');
    // Check email block has styles too
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    // Save form
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_label"]', 'style', 'font-weight: bold;');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');

    // Check styles are applied on frontend page
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->assertAttributeContains('[data-automation-id="form_first_name"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="form_first_name_label"]', 'style', 'font-weight: bold;');
    $i->assertAttributeContains('[data-automation-id="form_email"]', 'style', 'border-width: 10px;');
  }
}
