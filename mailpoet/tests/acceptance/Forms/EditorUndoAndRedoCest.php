<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorUndoAndRedoCest {
  public function addNamesToAForm(\AcceptanceTester $i) {
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
    // Add First name block
    $i->addFromBlockInEditor('First name');
    $firstNameSelector = '[data-automation-id="editor_first_name_input"]';
    $i->waitForElementVisible($firstNameSelector);
    // Click on undo
    $i->click('[data-automation-id="form_undo_button"]');
    $i->waitForElementNotVisible($firstNameSelector);
    // Click on redo
    $i->click('[data-automation-id="form_redo_button"]');
    $i->waitForElementVisible($firstNameSelector);
    // Add Last name block
    $i->addFromBlockInEditor('Last name');
    $lastNameSelector = '[data-automation-id="editor_last_name_input"]';
    $i->waitForElementVisible($lastNameSelector);
    // Two clicks on undo and we should see form without last name and first name
    $i->click('[data-automation-id="form_undo_button"]');
    $i->click('[data-automation-id="form_undo_button"]');
    $i->waitForElementNotVisible($lastNameSelector);
    $i->waitForElementNotVisible($firstNameSelector);
  }
}
