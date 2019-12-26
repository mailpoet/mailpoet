<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorUpdateMandatoryFieldsCest {
  public function updateEmailAndSubmit(\AcceptanceTester $I) {
    $features = new Features();
    $features->withFeatureEnabled(FeaturesController::NEW_FORM_EDITOR);
    $segment_factory = new Segment();
    $form_name = 'Mandatory fields test';
    $form_factory = new Form();
    $form_factory
      ->withSegments([$segment_factory->withName('Fancy List')->create()])
      ->withName($form_name)
      ->create();
    $I->wantTo('Update form mandatory fields');
    $I->login();
    // Open form for editation
    $I->amOnMailPoetPage('Forms');
    $I->waitForText($form_name);
    $I->clickItemRowActionByItemName($form_name, 'Edit');
    $I->waitForElement('[data-automation-id="form_title_input"]');
    // Change email label
    $block_email_input = '[data-automation-id="editor_email_input"]';
    $I->click($block_email_input);
    $block_settings_email_label_input = '[data-automation-id="settings_email_label_input"]';
    $I->waitForElement($block_settings_email_label_input);
    $updated_email_label = 'Your email';
    $I->fillField($block_settings_email_label_input, $updated_email_label);
    $I->see($updated_email_label, '[data-automation-id="editor_email_label"]');
    $I->seeNoJSErrors();
    // Change submit label
    $block_submit_input = '[data-automation-id="editor_submit_input"]';
    $I->click($block_submit_input);
    $block_settings_submit_label_input = '[data-automation-id="settings_submit_label_input"]';
    $updated_submit_label = 'Hey hey subscribe!';
    $I->waitForElement($block_settings_submit_label_input);
    $I->fillField($block_settings_submit_label_input, $updated_submit_label);
    $I->assertAttributeContains($block_submit_input, 'value', $updated_submit_label);
    $I->seeNoJSErrors();
    // Save changes
    $I->click('[data-automation-id="form_save_button"]');
    $I->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $I->seeNoJSErrors();
    // Reload page and check data were saved
    $I->reloadPage();
    $I->waitForElement('[data-automation-id="form_title_input"]');
    $I->assertAttributeContains($block_submit_input, 'value', $updated_submit_label);
    $I->see($updated_email_label, '[data-automation-id="editor_email_label"]');
    $I->seeNoJSErrors();
  }
}
