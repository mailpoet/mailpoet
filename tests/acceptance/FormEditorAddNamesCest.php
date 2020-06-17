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
    $i->wantTo('Add first and last name to the editor');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->addFromBlockInEditor('First name');
    $i->addFromBlockInEditor('Last name');

    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeElement('[data-automation-id="editor_first_name_input"]');
    $i->seeElement('[data-automation-id="editor_last_name_input"]');
  }
}
