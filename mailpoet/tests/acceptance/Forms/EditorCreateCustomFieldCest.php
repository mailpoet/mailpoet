<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
class EditorCreateCustomFieldCest {
  private function prepareTheForm(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->withDisplayBelowPosts()->create();
  }

  private function openFormInEditor(\AcceptanceTester $i) {
    $formName = 'My fancy form';
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
  }

  public function _before(\AcceptanceTester $i) {
    // Make sure the confirmation is enabled to have correct message
    $settings = new Settings();
    $settings->withConfirmationEmailEnabled();
    // Prepare the form for testing
    $this->prepareTheForm($i);
    // Go and edit the form
    $this->openFormInEditor($i);
    // Insert create custom field block
    $i->addFromBlockInEditor('Create Custom Field');
  }

  public function createCustomSelect(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: select');
    $i->wantTo('Configure, check and save the custom field block');
    $customFieldName = 'My custom select';
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Select');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'Custom label');
    $i->click('.components-form-toggle__input'); // Toggle as mandatory field
    $i->waitForElement('[data-automation-id="custom_field_value_settings"]');
    $i->fillField('[data-automation-id="custom_field_value_settings_value"]', 'First option'); // Configure first option
    $i->click('[data-automation-id="custom_field_values_add_item"]'); // Add second option
    $this->saveCustomFieldBlock($i);
    $this->checkCustomSelectInForm($i);

    $i->wantTo('Add third option, save, reload and check data were saved');
    $i->click('[data-automation-id="custom_field_values_add_item"]'); // Add third option
    $i->click('[data-automation-id="custom_field_save"]');
    $i->waitForText('Custom field saved.');
    $i->saveFormInEditor();
    $i->reloadPage();
    $this->checkCustomSelectInForm($i);

    $i->wantTo('Check custom select on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->see($customFieldName);
    $i->see('First option');
    $i->see('Option 2');
    $i->see('Option 3');
    $i->fillField('[data-automation-id="form_email"]', 'test@fake.fake');
    $i->click('.mailpoet_submit');
    $i->waitForText('Missing value for custom field "' . $customFieldName . '"');
    $i->selectOption('.mailpoet_select', 'Option 3');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
  }

  public function createCustomTextInput(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: text input');
    $i->wantTo('Configure, check and save the custom field block');
    $customFieldName = 'My custom text input';
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Text Input');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'Custom label');
    $i->click('.components-form-toggle__input'); // Toggle as mandatory field
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Alphanumerical');
    $this->saveCustomFieldBlock($i);

    $i->wantTo('Save, reload and check data were saved');
    $i->saveFormInEditor();
    $i->reloadPage();
    $this->checkCustomTextInputInForm($i);

    $i->wantTo('Change text input validation');
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');

    $i->wantTo('Update label and save the form');
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', $customFieldName . ' updated');
    $i->click('[data-automation-id="custom_field_save"]');
    $i->waitForText('Custom field saved.');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="editor_custom_text_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_text_input"]', 'placeholder', $customFieldName . ' updated');

    $i->wantTo('Check custom text input on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->seeElement(Locator::find('input', ['placeholder' => $customFieldName . ' updated *']));
    $i->fillField('[data-automation-id="form_email"]', 'test@fake.fake');
    $i->click('.mailpoet_submit');
    $i->waitForText('This value should be a valid number.');
    $i->fillField(Locator::find('input', ['placeholder' => $customFieldName . ' updated *']), 'Lorem ipsum dolor');
    $i->click('.mailpoet_submit');
    $i->waitForText('This value should be a valid number.');
    $i->fillField(Locator::find('input', ['placeholder' => $customFieldName . ' updated *']), '12345');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
  }

  public function createCustomTextArea(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: text area');
    $i->wantTo('Configure, check and save the custom field block');
    $customFieldName = 'My custom text area';
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Text Area');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'Custom label');
    $i->click('.components-form-toggle__input'); // Toggle as mandatory field
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');
    $this->saveCustomFieldBlock($i);

    $i->wantTo('Save, reload and check data were saved');
    $i->saveFormInEditor();
    $i->reloadPage();
    $this->checkCustomTextAreaInForm($i);

    $i->wantTo('Change text input validation');
    $i->selectOption('[data-automation-id="settings_custom_text_input_validation_type"]', 'Alphanumerical');
    $i->wantTo('Update label and save the form');
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', $customFieldName . ' updated');
    $i->click('[data-automation-id="custom_field_save"]');
    $i->waitForText('Custom field saved.');

    $i->wantTo('Change text area to 3 lines');
    $i->selectOption('[data-automation-id="settings_custom_text_area_number_of_lines"]', '3 lines');

    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="editor_custom_textarea_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_textarea_input"]', 'placeholder', $customFieldName . ' updated');
    $i->click('[data-automation-id="editor_custom_textarea_input"]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_text_area_number_of_lines"]', '3 lines');

    $i->wantTo('Check custom text area on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'test@fake.fake');
    $i->assertAttributeContains('[data-automation-id="form_custom_text_area"]', 'placeholder', $customFieldName . ' updated *');
    $i->click('.mailpoet_submit');
    $i->waitForText('This value should be alphanumeric.');
    $i->fillField('[data-automation-id="form_custom_text_area"]', 'Loremipsumdolor12345');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
  }

  public function createCustomRadioButtons(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: radio buttons');
    $i->wantTo('Configure, check and save the custom field block');
    $customFieldName = 'My custom radio buttons';
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Radio buttons');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'Custom label');
    $i->click('.components-form-toggle__input'); // Toggle as mandatory field
    $i->fillField('[data-automation-id="custom_field_value_settings_value"]', 'Option 1');
    $i->click('[data-automation-id="custom_field_values_add_item"]');
    $this->saveCustomFieldBlock($i);

    $i->wantTo('Save, reload and check data were saved');
    $i->saveFormInEditor();
    $i->reloadPage();
    $this->checkCustomRadioButtonsInForm($i, 'Option 1');

    $i->wantTo('Change text input validation');
    $i->fillField('[data-automation-id="custom_field_value_settings_value"][value="Option 1"]', 'New option');

    $i->wantTo('Update label, add third option and save the form');
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', $customFieldName . ' updated');
    $i->click('[data-automation-id="custom_field_values_add_item"]'); // Add third option
    $i->click('[data-automation-id="custom_field_save"]');
    $i->waitForText('Custom field saved.');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $this->checkCustomRadioButtonsInForm($i, 'New option');

    $i->wantTo('Check radio buttons on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'test@fake.fake');
    $i->waitForText($customFieldName . ' updated *');
    $i->waitForText('New option');
    $i->waitForText('Option 2');
    $i->waitForText('Option 3');
    $i->click('.mailpoet_submit');
    $i->waitForText('Please select at least one option.');
    $i->click('(//input[@class="mailpoet_radio"])[1]'); // Select the first radio button
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
  }

  public function createCustomCheckbox(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: checkbox');
    $i->wantTo('Configure, check and save the custom field block');
    $customFieldName = 'My custom checkbox';
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Checkbox');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', 'Custom label');
    $i->click('.components-form-toggle__input'); // Toggle as mandatory field
    $i->fillField('[data-automation-id="settings_custom_checkbox_value"]', 'Option 1');
    $this->saveCustomFieldBlock($i);

    $i->wantTo('Save, reload and check data were saved');
    $i->saveFormInEditor();
    $i->reloadPage();
    $this->checkCustomCheckboxInForm($i, 'Option 1');

    $i->wantTo('Change text input validation');
    $i->fillField('[data-automation-id="settings_custom_checkbox_value"][value="Option 1"]', 'New option');

    $i->wantTo('Update label and save the form');
    $i->fillField('[data-automation-id="settings_custom_text_label_input"]', $customFieldName . ' updated');
    $i->click('[data-automation-id="custom_field_save"]');
    $i->waitForText('Custom field saved.');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $this->checkCustomCheckboxInForm($i, 'New option');

    $i->wantTo('Check checkbox on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'test@fake.fake');
    $i->waitForText($customFieldName . ' updated *');
    $i->waitForText('New option');
    $i->click('.mailpoet_submit');
    $i->waitForText('Please select at least one option.');
    $i->click('.mailpoet_checkbox'); // Select the checkbox
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
  }

  public function createCustomDate(\AcceptanceTester $i) {
    $i->wantTo('Create custom field: date');
    $i->wantTo('Configure, check and save the custom field block');
    $customFieldName = 'My custom date';
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');
    $i->selectOption('[data-automation-id="create_custom_field_type_select"]', 'Date');
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->fillField('[data-automation-id="settings_custom_date_label_input"]', 'Custom label');
    $i->click('(//input[@class="components-form-toggle__input"])[1]'); // Toggle as mandatory field
    $i->click('(//input[@class="components-form-toggle__input"])[2]'); // Toggle preselect today's date
    $i->fillField('[data-automation-id="create_custom_field_name_input"]', $customFieldName);
    $i->selectOption('[data-automation-id="settings_custom_date_type"]', 'Year, month');
    $i->selectOption('[data-automation-id="settings_custom_date_format"]', 'YYYY/MM');
    $this->saveCustomFieldBlock($i);

    $i->wantTo('Save, reload and check data were saved');
    $i->saveFormInEditor();
    $i->reloadPage();
    $this->checkCustomDateInForm($i);

    $i->wantTo('Change date type and verify you do not see format anymore');
    $i->selectOption('[data-automation-id="settings_custom_date_type"]', 'Year');
    $i->dontSee('[data-automation-id="settings_custom_date_format"]');

    $i->wantTo('Update label and save the form');
    $i->selectOption('[data-automation-id="settings_custom_date_type"]', 'Year, month');
    $i->fillField('[data-automation-id="settings_custom_date_label_input"]', $customFieldName . ' updated');
    $i->click('[data-automation-id="custom_field_save"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="editor_custom_date_label"]');
    $i->click('[data-automation-id="editor_custom_date_label"]');
    $i->seeInField('[data-automation-id="settings_custom_date_label_input"]', $customFieldName . ' updated');

    $i->wantTo('Check custom date on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForElementVisible('[data-automation-id="form_email"]');
    $i->fillField('[data-automation-id="form_email"]', 'test@fake.fake');
    $i->waitForText($customFieldName . ' updated *');
    $i->assertAttributeContains('[data-automation-id="form_date_year"]', 'placeholder', 'Year');
    $i->assertAttributeContains('[data-automation-id="form_date_month"]', 'placeholder', 'Month');
    $i->click('.mailpoet_submit');
    $i->waitForText('Please select a year');
    $i->waitForText('Please select a month');
    $i->selectOption('[data-automation-id="form_date_year"]', '2000');
    $i->selectOption('[data-automation-id="form_date_month"]', 'January');
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.');
  }

  private function checkCustomDateInForm(\AcceptanceTester $i) {
    $i->waitForElement('[data-automation-id="editor_custom_date_label"]');
    $i->click('[data-automation-id="editor_custom_date_label"]');
    $i->seeCheckboxIsChecked('(//input[@class="components-form-toggle__input"])[1]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_date_type"]', 'Year, month');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_date_format"]', 'YYYY/MM');
  }

  private function checkCustomCheckboxInForm(\AcceptanceTester $i, $name) {
    $i->waitForElement('[data-automation-id="editor_custom_field_checkbox_block"]');
    $i->click('[data-automation-id="editor_custom_field_checkbox_block"]');
    $i->waitForElement('[data-automation-id="settings_custom_checkbox_value"][value="' . $name . '"]');
    $i->seeCheckboxIsChecked('(//input[@class="components-form-toggle__input"])[1]');
  }

  private function checkCustomRadioButtonsInForm(\AcceptanceTester $i, $name) {
    $i->waitForElement('[data-automation-id="editor_custom_field_radio_buttons_block"]');
    $i->click('[data-automation-id="editor_custom_field_radio_buttons_block"]');
    $i->waitForElement('[data-automation-id="custom_field_settings"]');
    $i->seeCheckboxIsChecked('(//input[@class="components-form-toggle__input"])[1]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="' . $name . '"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="Option 2"]');
  }

  private function checkCustomTextAreaInForm(\AcceptanceTester $i) {
    $i->waitForElement('[data-automation-id="editor_custom_textarea_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_textarea_input"]', 'placeholder', 'My custom text area');
    $i->click('[data-automation-id="editor_custom_textarea_input"]');
    $i->seeCheckboxIsChecked('(//input[@class="components-form-toggle__input"])[1]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_text_input_validation_type"]', 'Numbers only');
  }

  private function checkCustomTextInputInForm(\AcceptanceTester $i) {
    $i->waitForElement('[data-automation-id="editor_custom_text_input"]');
    $i->assertAttributeContains('[data-automation-id="editor_custom_text_input"]', 'placeholder', 'My custom text input');
    $i->click('[data-automation-id="editor_custom_text_input"]');
    $i->seeCheckboxIsChecked('(//input[@class="components-form-toggle__input"])[1]');
    $i->seeOptionIsSelected('[data-automation-id="settings_custom_text_input_validation_type"]', 'Alphanumerical');
  }

  private function checkCustomSelectInForm(\AcceptanceTester $i) {
    $i->waitForElement('[data-automation-id="custom_select_block"]');
    $i->click('[data-automation-id="custom_select_block"]');
    $i->waitForElement('[data-automation-id="custom_field_settings"]');
    $i->seeCheckboxIsChecked('(//input[@class="components-form-toggle__input"])[1]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="First option"]');
    $i->waitForElement('[data-automation-id="custom_field_value_settings_value"][value="Option 2"]');
  }

  private function saveCustomFieldBlock(\AcceptanceTester $i) {
    $i->click('[data-automation-id="create_custom_field_submit"]');
    $i->waitForText('Custom field saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    $i->click('.automation-dismissible-notices .components-notice__dismiss');
  }
}
