<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
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
    $i->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $i->waitForElement('.editor-inserter__results .components-panel__body-toggle');
    $i->click('.editor-inserter__results .components-panel__body:nth-child(2) .components-panel__body-toggle'); // toggle custom fields
    $i->click('.editor-block-list-item-mailpoet-form-add-custom-field'); // add create custom field block
    $i->waitForElement('[data-automation-id="create_custom_field_form"]');

    // Configure custom select custom field
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
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();

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
