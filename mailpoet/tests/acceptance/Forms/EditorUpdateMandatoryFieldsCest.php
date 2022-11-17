<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorUpdateMandatoryFieldsCest {
  public function updateEmailAndSubmit(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $formName = 'Mandatory fields test';
    $formFactory = new Form();
    $formFactory
      ->withSegments([$segmentFactory->withName('Fancy List')->create()])
      ->withName($formName)
      ->create();
    $i->wantTo('Update form mandatory fields');
    $i->login();
    // Open form for editation
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    // Change email label
    $blockEmailInput = '[data-automation-id="editor_email_input"]';
    $i->click($blockEmailInput);
    $blockSettingsEmailLabelInput = '[data-automation-id="settings_email_label_input"]';
    $i->waitForElement($blockSettingsEmailLabelInput);
    $updatedEmailLabel = 'Your email';
    $i->fillField($blockSettingsEmailLabelInput, $updatedEmailLabel);
    $i->see($updatedEmailLabel, '[data-automation-id="editor_email_label"]');
    $i->seeNoJSErrors();
    // Change submit label
    $blockSubmitInput = '[data-automation-id="editor_submit_input"]';
    $i->click($blockSubmitInput);
    $blockSettingsSubmitLabelInput = '[data-automation-id="settings_submit_label_input"]';
    $updatedSubmitLabel = 'Hey hey subscribe!';
    $i->waitForElement($blockSettingsSubmitLabelInput);
    $i->fillField($blockSettingsSubmitLabelInput, $updatedSubmitLabel);
    $i->assertAttributeContains($blockSubmitInput, 'value', $updatedSubmitLabel);
    $i->seeNoJSErrors();
    // Save changes
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->assertAttributeContains($blockSubmitInput, 'value', $updatedSubmitLabel);
    $i->see($updatedEmailLabel, '[data-automation-id="editor_email_label"]');
    $i->seeNoJSErrors();
  }
}
