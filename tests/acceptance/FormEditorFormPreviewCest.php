<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorFormPreviewCest {
  public function previewUnsavedChangesAndRememberPreviewSettings(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    $i->wantTo('Add first name to the editor and preview form without saving it');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $i->waitForElement('.block-editor-inserter__results .components-panel__body-toggle');
    $i->click('.block-editor-inserter__results .components-panel__body:nth-child(2) .components-panel__body-toggle'); // toggle fields
    $i->click('.editor-block-list-item-mailpoet-form-first-name-input'); // add first name block to the editor

    // Open preview
    $i->click('[data-automation-id="form_preview_button"]');
    $i->waitForElement('[data-automation-id="form_preview_iframe"]');

    // Check first name was rendered in iframe
    $i->switchToIFrame('[data-automation-id="form_preview_iframe"]');
    $i->waitForElement('[data-automation-id="form_first_name"]');
    $i->switchToIFrame();

    // Change preview type and form type and check again
    $i->click('[data-automation-id="preview_type_mobile"]');
    $i->selectOption('[data-automation-id="form_type_selection"]', 'Fixed bar');
    $i->switchToIFrame('[data-automation-id="form_preview_iframe"]');
    $i->waitForElement('[data-automation-id="form_first_name"]');
    $i->switchToIFrame();

    // Reload page and check preview settings
    $i->reloadPage();
    $i->acceptPopup();
    $i->waitForElement('[data-automation-id="form_preview_button"]');
    $i->click('[data-automation-id="form_preview_button"]');
    $i->waitForElement('[data-automation-id="form_preview_iframe"]');
    $i->seeOptionIsSelected('[data-automation-id="form_type_selection"]', 'Fixed bar');
    $i->seeOptionIsSelected('[data-automation-id="form_type_selection"]', 'Fixed bar');
  }
}
