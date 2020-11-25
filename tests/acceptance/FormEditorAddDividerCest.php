<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorAddDividerCest {
  public function addDividerBlock(\AcceptanceTester $i) {
    $i->wantTo('Add divider block to the editor');
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();

    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    // Add divider & spacer block
    $i->addFromBlockInEditor('Divider / Spacer');

    // Check and modify divider & spacer block
    $i->assertAttributeContains('[data-automation-id="editor_divider_block"]', 'style', 'border-top: 1px solid black;');
    $i->click('[data-automation-id="settings_toggle_enable_divider"]');
    $i->assertAttributeNotContains('[data-automation-id="editor_divider_block"]', 'style', 'border-top: 1px solid black;');
    $i->clearFormField('.mailpoet-automation-spacer-height-size input[type="number"]');
    $i->fillField('.mailpoet-automation-spacer-height-size input[type="number"]', 50);
    $i->assertAttributeContains('[data-automation-id="editor_spacer_block"]', 'style', 'height: 50px;');
    $i->click('[data-automation-id="settings_toggle_enable_divider"]');
    $i->selectOption('[data-automation-id="settings_divider_style"]', 'Dotted');
    $i->seeInField('.mailpoet-automation-styles-divider-height input[type="number"]', 1);
    $i->clearFormField('.mailpoet-automation-styles-divider-height input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-divider-height input[type="number"]', 10);
    $i->seeInField('.mailpoet-automation-styles-divider-width input[type="number"]', 100);
    $i->clearFormField('.mailpoet-automation-styles-divider-width input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-divider-width input[type="number"]', 10);

    // Save and reload page to check if data were saved
    $i->saveFormInEditor();
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->click('[data-automation-id="editor_divider_block"]');
    $i->assertAttributeContains('[data-automation-id="editor_spacer_block"]', 'style', 'height: 50px;');
    $i->seeInField('.mailpoet-automation-spacer-height-size input[type="number"]', 50);
    $i->seeInField('.mailpoet-automation-styles-divider-height input[type="number"]', 10);
    $i->seeInField('.mailpoet-automation-styles-divider-width input[type="number"]', 10);
  }
}
