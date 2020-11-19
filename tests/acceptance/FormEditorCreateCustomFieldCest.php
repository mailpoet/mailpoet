<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorCreateCustomFieldCest {
  private function prepareTheForm(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
  }

  private function editTheForm($i) {
    $formName = 'My fancy form';
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
  }

  public function createCustomSelect(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: select');
    // Prepare the form for testing
    $this->prepareTheForm($i);
    
    // Go and edit the form
    $this->editTheForm($i);

    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Select');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom select');
    $i->waitForElement('[data-automation-id="custom_field_value_settings"]');
    $i->fillField('[data-automation-id="custom_field_value_settings_value"]', 'First option'); // Configure first option
    $i->click('[data-automation-id="custom_field_values_add_item"]'); // Add second option

    // Save the custom field
    $this->saveCustomFieldBlock($i);

    // Check field was added correctly
    $this->checkCustomSelectInForm($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomSelectInForm($i);
  }

  public function createCustomTextInput(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: text input');
    // Prepare the form for testing
    $this->prepareTheForm($i);
    
    // Go and edit the form
    $this->editTheForm($i);

    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Text Input');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom text input');
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');

    // Save the custom field
    $this->saveCustomFieldBlock($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomTextInputInForm($i);

    // Change text input validation
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Alphanumerical');
    $i->click('Update custom field');
    $i->waitForText('Custom field saved.');

    // Update label and save the form
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'My updated custom text input');
    $i->saveFormInEditor();
    
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="editor_custom_text_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_text_input"]', 'placeholder', 'My updated custom text input');
  }

  public function createCustomTextArea(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: text area');
    // Prepare the form for testing
    $this->prepareTheForm($i);
    
    // Go and edit the form
    $this->editTheForm($i);

    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Text Area');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom text area');
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');

    // Save the custom field
    $this->saveCustomFieldBlock($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomTextAreaInForm($i);

    // Change text input validation
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Alphanumerical');
    $i->click('Update custom field');
    $i->waitForText('Custom field saved.');

    // Change text area to 3 lines
    $i->selectOption('[data-automation-id="settings_custom_text_area_number_of_lines"]', '3 lines');

    // Update label and save the form
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'My updated custom text area');
    $i->saveFormInEditor();
    
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="editor_custom_textarea_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_textarea_input"]', 'placeholder', 'My updated custom text area');
    $i->click('[data-automation-id="editor_custom_textarea_input"]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_text_area_number_of_lines"]', '3 lines');
  }

  public function createCustomRadioButtons(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: radio buttons');
    // Prepare the form for testing
    $this->prepareTheForm($i);
    
    // Go and edit the form
    $this->editTheForm($i);

    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Radio buttons');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom radio buttons');
    $i->fillField('[data-automation-id="custom_field_value_settings_value"]', 'Option 1');
    $i->click('[data-automation-id="custom_field_values_add_item"]');

    // Save the custom field
    $this->saveCustomFieldBlock($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomRadioButtonsInForm($i, 'Option 1');

    // Change text input validation
    $i->fillField('[data-automation-id="custom_field_value_settings_value"][value="Option 1"]', 'New option');
    $i->click('Update custom field');
    $i->waitForText('Custom field saved.');

    // Update label and save the form
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'My updated custom radio buttons');
    $i->saveFormInEditor();
    
    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomRadioButtonsInForm($i, 'New option');
  }

  public function createCustomCheckbox(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: checkbox');
    // Prepare the form for testing
    $this->prepareTheForm($i);
    
    // Go and edit the form
    $this->editTheForm($i);

    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Checkbox');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom checkbox');
    $i->fillField('[data-automation-id="settings_custom_checkbox_value"]', 'Option 1');

    // Save the custom field
    $this->saveCustomFieldBlock($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomCheckboxInForm($i, 'Option 1');

    // Change text input validation
    $i->fillField('[data-automation-id="settings_custom_checkbox_value"][value="Option 1"]', 'New option');
    $i->click('Update custom field');
    $i->waitForText('Custom field saved.');

    // Update label and save the form
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'My updated custom checkbox');
    $i->saveFormInEditor();
    
    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomCheckboxInForm($i, 'New option');
  }

  public function createCustomDate(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: date');
    // Prepare the form for testing
    $this->prepareTheForm($i);
    
    // Go and edit the form
    $this->editTheForm($i);

    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');

    // Configure custom select custom field
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Date');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', 'My custom date');
    $i->selectOption('[data-automation-id="settings_custom_date_type"]', 'Year, month');
    $i->selectOption('[data-automation-id="settings_custom_date_format"]', 'YYYY/MM');

    // Save the custom field
    $this->saveCustomFieldBlock($i);

    // Save the form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $this->checkCustomDateInForm($i);

    // Change date type and verify you don't see format anymore
    $i->selectOption('[data-automation-id="settings_custom_date_type"]', 'Year');
    $i->dontSee('[data-automation-id="settings_custom_date_format"]');

    // Update label and save the form
    $i->fillField('[data-automation-id="settings_custom_date_label_input"]', 'My updated custom date');
    $i->saveFormInEditor();
    
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="editor_custom_date_label"]');
    $i->click('[data-automation-id="editor_custom_date_label"]');
    $i->seeInField('[data-automation-id="settings_custom_date_label_input"]', 'My updated custom date');
  }

  private function checkCustomDateInForm($i) {
    $i->waitForElement('[data-automation-id="editor_custom_date_label"]');
    $i->click('[data-automation-id="editor_custom_date_label"]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_date_type"]', 'Year, month');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_date_format"]', 'YYYY/MM');
  }

  private function checkCustomCheckboxInForm($i, $name) {
    $i->waitForElement('[data-automation-id="editor_custom_field_checkbox_block"]');
    $i->click('[data-automation-id="editor_custom_field_checkbox_block"]');
    $i->waitForElement('[data-automation-id="settings_custom_checkbox_value"][value="'.$name.'"]');
  }

  private function checkCustomRadioButtonsInForm($i, $name) {
    $i->waitForElement('[data-automation-id="editor_custom_field_radio_buttons_block"]');
    $i->click('[data-automation-id="editor_custom_field_radio_buttons_block"]');
    $i->waitForElement('[data-automation-id="custom_field_settings"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="'.$name.'"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="Option 2"]');
  }

  private function checkCustomTextAreaInForm($i) {
    $i->waitForElement('[data-automation-id="editor_custom_textarea_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_textarea_input"]', 'placeholder', 'My custom text area');
    $i->click('[data-automation-id="editor_custom_textarea_input"]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');
  }

  private function checkCustomTextInputInForm($i) {
    $i->waitForElement('[data-automation-id="editor_custom_text_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_text_input"]', 'placeholder', 'My custom text input');
    $i->click('[data-automation-id="editor_custom_text_input"]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');
  }

  private function checkCustomSelectInForm($i) {
    $i->waitForElement('[data-automation-id="custom_select_block"]');
    $i->click('[data-automation-id="custom_select_block"]');
    $i->waitForElement('[data-automation-id="custom_field_settings"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="First option"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="Option 2"]');
  }

  private function saveCustomFieldBlock($i) {
    $i->click('[data-automation-id="create_custom_field_submit"]');
    $i->waitForText('Custom field saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    $i->click('.automation-dismissible-notices .components-notice__dismiss');
  }
}
