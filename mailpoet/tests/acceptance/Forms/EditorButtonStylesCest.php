<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorButtonStylesCest {
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

    $i->wantTo('Apply some styles to submit button');
    $i->click('[data-automation-id="editor_submit_input"]');
    $i->waitForElement('.mailpoet-automation-input-styles-panel');
    $i->fillField('[data-automation-id="settings_submit_label_input"]', 'Join Now');
    $i->click('.mailpoet-automation-input-styles-panel');
    $i->waitForElement('[data-automation-id="input_styles_settings"]');
    $i->click('.mailpoet-automation-inherit-theme-toggle input'); // Display custom settings
    $i->click('.mailpoet-automation-styles-bold-toggle input'); // Toggle bold on
    $i->clearFormField('.mailpoet-automation-styles-border-size input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-border-size input[type="number"]', 10); // Set border size

    $i->wantTo('Check element has styles');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'font-weight: bold;');

    $i->wantTo('Save form');
    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'border-width: 10px;');
    $i->assertAttributeContains('[data-automation-id="editor_submit_input"]', 'style', 'font-weight: bold;');

    $i->wantTo('Check styles are applied on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->assertCssProperty('[data-automation-id="subscribe-submit-button"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="subscribe-submit-button"]', 'font-weight', '700');
    $i->seeInField('[data-automation-id="subscribe-submit-button"]', 'Join Now');
  }
}
