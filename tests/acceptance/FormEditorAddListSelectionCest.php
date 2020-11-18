<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorAddListSelectionCest {
  public function createCustomSelect(\AcceptanceTester $i) {
    $i->wantTo('Add list selection block to the editor');
    $segmentFactory = new Segment();
    $firstSegmentName = 'First fancy list';
    $segment = $segmentFactory->withName($firstSegmentName)->create();
    $secondSegmentName = 'Second fancy list';
    $segment = $segmentFactory->withName($secondSegmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    // Insert list selection block
    $i->addFromBlockInEditor('List selection');

    // Verify that user must choose a list
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Please select a list');

    // Configure list selection block
    $i->waitForElement('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_block_settings_tab"]');
    $i->fillField('[data-automation-id="settings_first_name_label_input"]', 'Choose your list:');
    $i->selectOption('[data-automation-id="select_list_selections_list"]', $secondSegmentName);
    $i->seeNoJSErrors();

    // Save the form
    $i->saveFormInEditor();

    // Reload the page and check that data were saved
    $i->reloadPage();
    $this->checkListSelectionInForm($i);

    // Go back to the forms list and verify the attached list
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName);
    $i->waitForText('User choice:');
    $i->waitForText($secondSegmentName);
  }

  private function checkListSelectionInForm($i) {
    $i->waitForElement('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_list_selection_block"]');
    $i->seeInField('[data-automation-id="settings_first_name_label_input"]', 'Choose your list:');
    $i->seeOptionIsSelected('[data-automation-id="select_list_selections_list"]', $secondSegmentName);
  }
}
