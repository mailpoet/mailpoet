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

    $i->wantTo('Add first name');
    $i->addFromBlockInEditor('First name');

    $i->wantTo('Apply some styles to first name');
    $i->click('.mailpoet-automation-input-styles-panel');
    $i->waitForElement('[data-automation-id="input_styles_settings"]');
    $i->click('.mailpoet-automation-inherit-theme-toggle input'); // Display custom settings
    $i->click('.mailpoet-automation-styles-bold-toggle input'); // Toggle bold on
    $i->clearFormField('.mailpoet-automation-styles-border-size input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-border-size input[type="number"]', 10); // Set border size
    $i->click('.mailpoet-automation-label-within-input-toggle input'); // Toggle lable to be rendered outside the input

    $i->wantTo('Check element has styles');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_label"]', 'style', 'font-weight: bold;');

    $i->wantTo('Apply to all');
    $i->click('[data-automation-id="styles_apply_to_all"]');

    $i->wantTo('Add heading block and write some title');
    $i->addFromBlockInEditor('Heading');
    $i->fillField('[data-title="Heading"]', 'Lorem Ipsum');
    $i->see('Lorem Ipsum');

    $i->wantTo('Add paragraph block and write some text');
    $i->addFromBlockInEditor('Paragraph');
    $i->fillField('[data-title="Paragraph"]', 'Lorem ipsum dolor sit amet');
    $i->see('Lorem ipsum dolor sit amet');

    $i->wantTo('Check email block has styles too and save the form');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_label"]', 'style', 'font-weight: bold;');
    $i->assertAttributeContains('[data-automation-id="editor_first_name_input"]', 'style', 'border-width: 10px;');
    $i->see('Lorem Ipsum');
    $i->see('Lorem ipsum dolor sit amet');

    $i->wantTo('Check styles are applied on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->assertAttributeContains('[data-automation-id="form_first_name"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="form_first_name_label"]', 'style', 'font-weight: bold;');
    $i->assertAttributeContains('[data-automation-id="form_email"]', 'style', 'border-width: 10px;');
  }
}
