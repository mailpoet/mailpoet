<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorButtonStylesCest {
  public function changeSubmitButtonStyles(\AcceptanceTester $i) {
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

    // Apply some styles to submit button
    $i->click('[data-automation-id="editor_submit_input"]');
    $i->waitForElement('.mailpoet-automation-input-styles-panel');
    $i->click('.mailpoet-automation-input-styles-panel');
    $i->waitForElement('[data-automation-id="input_styles_settings"]');
    $i->click('.mailpoet-automation-inherit-theme-toggle input'); // Display custom settings
    $i->click('.mailpoet-automation-styles-bold-toggle input'); // Toggle bold on
    $i->clearFormField('.mailpoet-automation-styles-border-size input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-border-size input[type="number"]', 10); // Set border size

    // Check element has styles
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'font-weight: bold;');
    // Save form
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'font-weight: bold;');

    // Check styles are applied on frontend page
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->assertAttributeContains('[data-automation-id="subscribe-submit-button"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="subscribe-submit-button"]', 'style', 'font-weight: bold;');
  }
}
