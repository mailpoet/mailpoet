<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorAddNamesCest {
  public function addNamesToAForm(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    $firstNameLabelModified = 'Your First Name';
    $lastNameLabelModified = 'Your Last Name';
    $i->wantTo('Add first and last name to the editor');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    // Add First & Last name blocks
    $i->addFromBlockInEditor('First name');
    $i->addFromBlockInEditor('Last name');
    // Modify First & Last name blocks
    $i->click('[data-automation-id="editor_first_name_input"]');
    $i->fillField('[data-automation-id="settings_first_name_label_input"]', $firstNameLabelModified);
    $i->click('[data-automation-id="editor_last_name_input"]');
    $i->fillField('[data-automation-id="settings_last_name_label_input"]', $lastNameLabelModified);
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->click('[data-automation-id="editor_first_name_input"]');
    $i->seeInField('[data-automation-id="settings_first_name_label_input"]', $firstNameLabelModified);
    $i->click('[data-automation-id="editor_last_name_input"]');
    $i->seeInField('[data-automation-id="settings_last_name_label_input"]', $lastNameLabelModified);
  }
}
