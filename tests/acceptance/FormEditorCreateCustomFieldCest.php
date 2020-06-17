<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorCreateCustomFieldCest {
  public function createCustomSelect(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    $i->wantTo('Add first and last name to the editor');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Select');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom select');
    $i->waitForElement('[data-automation-id="custom_field_value_settings"]');
    $i->fillField('[data-automation-id="custom_field_value_settings_value"]', 'First option'); // Configure first option
    $i->click('[data-automation-id="custom_field_values_add_item"]'); // Add second option

    // Save custom field
    $i->click('[data-automation-id="create_custom_field_submit"]');
    $i->waitForText('Custom field saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    $i->click('.automation-dismissible-notices .components-notice__dismiss'); // Hide notice

    // Check field was added correctly
    $this->checkCustomSelectInForm($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomSelectInForm($i);
  }

  private function checkCustomSelectInForm($i) {
    $i->waitForElement('[data-automation-id="custom_select_block"]');
    $i->click('[data-automation-id="custom_select_block"]');
    $i->waitForElement('[data-automation-id="custom_field_settings"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="First option"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="Option 2"]');
  }
}
